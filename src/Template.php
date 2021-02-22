<?php

declare(strict_types=1);

namespace Dhii\Output\DelimitedTokenTemplate;

use ArrayAccess;
use Dhii\Output\Template\TemplateInterface;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;

/**
 * A template that uses delimited tokens as placeholders.
 *
 * The delimiters are configurable. So, it's possible to have your tokens look
 * like Handlebar tokens, for example.
 *
 * The token name can be anything that does not contain the right delimiter.
 * So, if the delimiters are '{{' and '}}', then given the string
 * '{{name!@#$%^&*()_+=-0987654321}}', the token name will be 'name!@#$%^&*()_+=-0987654321'.
 *
 * It is possible to escape the delimiters. As such, with the same delimiters as above,
 * and the escape char being '\', the template '\{{ {{name}}' will have the token 'name'
 * extracted, and if 'name' is 'Mary', the rendered result would be '{{ Mary'.
 * If the first left delimiter was not escaped, i.e. '{{ {{name}}, the token name would
 * be ' {{name', and the rendering would replace it with the value.
 * The in the rendered result, escape sequences will be replaced with their literal
 * values, as shown in the first escaping example.
 *
 * When matching the right delimiter, the first occurrence will be matched.
 * Thus, to have the right delimiter in the token name,
 * the template could be '{{name}\}}}', and then the token name must be 'name{\{'
 * in the context. Note that the escape char comes after the first '}', not before.
 * If the template was '{{name\}}}}', the token name extracted would be 'name\}', because
 * the right delimiter '}}' follows immediately after.
 *
 * Escaped delimiters must be present literally (i.e. without escape char) in the context
 * keys in order to get used for replacement. In the above example, 'name{\{' still
 * contains the escape char because the delimiter is not whole. If the token
 * name was 'name\{{' in the template, the key would need to be 'name{{' however.
 *
 * It is possible to use only one of the delimiters and omit the other. This could
 * be useful to render things that use syntax similar to prepared SQL statements,
 * e.g. '/users/:username/profile', where ':username' is a token that is delimited
 * only by a colon on the left. If only one of the delimiters is present (i.e.
 * if the other is an empty string), the token name can only contain alphanumeric
 * characters and '.', '-', and '_', while the escape character becomes irrelevant
 * and is ignored. In other cases, i.e. when both delimiters are present, the token
 * key can contain any characters, as long as they are not a delimiter, and it is
 * possible to escape the delimiters.
 */
class Template implements TemplateInterface
{
    /**
     * @var string
     */
    protected $leftDelimiter;
    /**
     * @var string
     */
    protected $rightDelimiter;
    /**
     * @var string
     */
    protected $template;
    /**
     * @var string
     */
    protected $escapeChar;

    /**
     * @param string $template       A template string that may contains tokens.
     * @param string $leftDelimiter  A string that appears to the right of the token name.
     * @param string $rightDelimiter A string that appears to the left of the token name.
     * @param string $escapeChar     The character that is used to escape token delimiters in the template string.
     */
    public function __construct(
        string $template,
        string $leftDelimiter,
        string $rightDelimiter,
        string $escapeChar
    ) {
        $this->template = $template;
        $this->leftDelimiter = $leftDelimiter;
        $this->rightDelimiter = $rightDelimiter;
        $this->escapeChar = $escapeChar;
    }

    /**
     * @inheritDoc
     */
    public function render($context = null)
    {
        return $this->replaceTokens($this->template, $context);
    }

    /**
     * Replaces all tokens in a string with corresponding values from the context.
     *
     * @param string                               $template The string to replace the tokens in.
     * @param array|ArrayAccess|ContainerInterface $context  The map of keys to context values.
     *
     * @return string A string with tokens replaced by values.
     *                If value not found in context, token will remain unchanged.
     */
    protected function replaceTokens(string $template, $context): string
    {
        $result = $template;
        $tokens = $this->getTokens($template);

        /* Token names will contain original escape sequences when matched.
         * These are replaced with literal values of those sequences here.
         */
        $tokens = $this->cleanTokens($tokens);

        // Replace tokens with their values from context
        foreach ($tokens as $key => $token) {
            $value = $this->getContextValue($context, $key, null);
            if ($value === null) {
                continue;
            }

            $result = str_replace($token, $value, $result);
        }

        /* Template strings will contain original escape sequences when matched.
         * These are replaced with literal values of those sequences here.
         */
        $result = $this->cleanEscapedDelimiters($result);

        return $result;
    }

    /**
     * Retrieves all tokens in the specified string.
     *
     * @param string $string The string to look for tokens in.
     *
     * @return array A map of token key to full token text.
     *               E.g. for token text "{{hello}}" the key would be "hello";
     */
    protected function getTokens(string $string): array
    {
        $d = '/';
        $l = preg_quote($this->leftDelimiter, $d);
        $r = preg_quote($this->rightDelimiter, $d);
        $e = preg_quote('\\', $d);
        $lExpr = strlen($l)
            ? "(?<ldelim>(?<!{$e}){$l})" // The left delimiter unless escaped
            : ''; // No delimiter
        $rExpr = strlen($r)
            ? "(?<rdelim>(?<!{$e}){$r}(?!{$r}}))" // The right delimiter unless escaped
            : ''; // No delimiter
        $nameExpr = strlen($l) && strlen($r)
            ? "(?!{$r}).+?" // anything that is not the right delimiter
            : "[\w\d_\-.]+"; // Alphanumeric, underscore, dash, and dot

        $expression =
            "{$d}" . // Open expr
                $lExpr . // left token delimiter
                "(?<name>" . // token name
                    $nameExpr .
                ")" .
                $rExpr . // right token delimiter
            "{$d}" // Close expr
        ;
        preg_match_all($expression, $string, $matches, PREG_PATTERN_ORDER);

        $tokens = array_combine($matches['name'], $matches[0]);
        $tokens = array_unique($tokens);

        return $tokens;
    }

    /**
     * Retrieves a value from a context by key.
     *
     * @param array|ArrayAccess|ContainerInterface $context The context.
     * @param string                               $key     The key to retrieve the value for.
     * @param mixed                                $default The value to return if key not found in context.
     *
     * @return mixed The value for the given key, or the default value if not found.
     *
     * @throws InvalidArgumentException If context is invalid.
     */
    protected function getContextValue($context, string $key, $default)
    {
        if (is_array($context)) {
            return array_key_exists($key, $context)
                ? $context[$key]
                : $default;
        }

        if ($context instanceof ArrayAccess) {
            return $context->offsetExists($key)
                ? $context->offsetGet($key)
                : $default;
        }

        /** @psalm-suppress RedundantConditionGivenDocblockType Not guaranteed by typehint */
        if ($context instanceof ContainerInterface) {
            return $context->has($key)
                ? $context->get($key)
                : $default;
        }

        throw new InvalidArgumentException('Invalid context');
    }

    /**
     * Cleans escaped delimiters from a string
     *
     * @param string $string The string to clean escaped delimiters from.
     *
     * @return string The string with escaped delimiters replaced with literal delimiters.
     */
    protected function cleanEscapedDelimiters(string $string): string
    {
        $e = $this->escapeChar;

        if (!strlen($e)) {
            return $string;
        }

        $ld = $this->leftDelimiter;
        $rd = $this->rightDelimiter;

        $string = str_replace(["{$e}{$ld}", "{$e}{$rd}"], [$ld, $rd], $string);

        return $string;
    }

    /**
     * Cleans escaped delimiters from a token map.
     *
     * @param array $tokens The map of token names to token strings.
     *
     * @return array The token map with escaped delimiters replaced with literal delimiters in keys.
     */
    protected function cleanTokens(array $tokens): array
    {
        $e = $this->escapeChar;

        if (!strlen($e)) {
            return $tokens;
        }

        $newTokens = [];
        foreach ($tokens as $name => $token) {
            $name = $this->cleanEscapedDelimiters($name);
            $newTokens[$name] = $token;
        }

        return $newTokens;
    }
}
