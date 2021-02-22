<?php

declare(strict_types=1);

namespace Dhii\Output\DelimitedTokenTemplate;

use Dhii\Output\Template\StringTemplateFactoryInterface;
use Dhii\Output\Template\TemplateInterface;

/**
 * A factory that creates delimited token templates.
 */
class TemplateFactory implements StringTemplateFactoryInterface
{
    /** @var string */
    protected $leftDelimiter;
    /** @var string */
    protected $rightDelimiter;
    /** @var string */
    protected $escapeChar;

    /**
     * @param string $leftDelimiter  The left delimiter of the templates.
     * @param string $rightDelimiter The right delimiter of the templates.
     * @param string $escapeChar     The char used to escape delimiters.
     */
    public function __construct(
        string $leftDelimiter,
        string $rightDelimiter,
        string $escapeChar
    ) {

        $this->leftDelimiter = $leftDelimiter;
        $this->rightDelimiter = $rightDelimiter;
        $this->escapeChar = $escapeChar;
    }

    /**
     * @inheritDoc
     */
    public function fromString(string $template): TemplateInterface
    {
        return new Template(
            $template,
            $this->leftDelimiter,
            $this->rightDelimiter,
            $this->escapeChar
        );
    }
}
