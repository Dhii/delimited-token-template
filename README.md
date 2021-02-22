# Delimited Token Template
A flexible implementation for handlebars-style templates.

[![Continuous Integration](https://github.com/Dhii/delimited-token-template/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/Dhii/delimited-token-template/actions/workflows/continuous-integration.yml)
[![Latest Stable Version](https://poser.pugx.org/dhii/delimited-token-template/v)](//packagist.org/packages/dhii/delimited-token-template)
[![Latest Unstable Version](https://poser.pugx.org/dhii/delimited-token-template/v/unstable)](//packagist.org/packages/dhii/delimited-token-template)

## Details
This template implementation is useful if you need to replace simple tokens with values.
Good examples are emails, which contain text like "Hello, %username%", or paths with 
variable segments, such as "/users/:username/profile".

The template supports any string delimiters: '[\*placeholder*\]', '~placeholder',
and "{{placeholder}}" will all work. It's possible to have the left delimiter
different from the right one, have delimiters which contain more than one character,
or to even omit _one_ of the delimiters.

When two delimiters are used, they can be escaped by a configurable escape character
to make them be used literally in the rendered result. With the left and right
delimiters being "{{" and "}}" respectively, and the escape char being "\",
the string "\{{ {{username}}" could produce "{{ johnny" if rendered with context
`['username' => 'johnny']`. If the second left delimiter was escaped instead,
i.e. "Hello, {{ \{{username}}", rendering with `[' {{username' => 'johnny']` would
produce "Hello, johnny". This demonstrates that escaped delimiters will be replaced
with their literal value in both the end result, and in token names.

Using one delimiter is also possible, e.g. "Hello, :username!" would produce
"Hello, johnny!" when rendered with context `['username' => 'johnny']`. In this
case, however, contrary to using two delimiters, token names are limited to
alphanumeric chars, as well as '_', '-', and '.' (underscore, dash, and period).
In addition, it is not possible to escape delimiters. Consequently, token names
cannot contain delimiters.

### Usage
#### Two delimiters
This example uses two identical delimiters, and an escaped delimiter
in the middle of the token name.

```php
use Dhii\Output\DelimitedTokenTemplate\Template;

$template = new Template('Hello, %user\%name%!', '%', '%', '\\'); // Note escaped delimiter
$template->render(['user%name' => 'johnny']); // Hello, johnny! 
```

#### Different long delimiters
This example uses two different delimiters that are longer
than 1 character, and of different lengths.

```php
use Dhii\Output\DelimitedTokenTemplate\Template;

$template = new Template('Hello, -user\-name__!', '-', '__', '\\'); // Note completely different delimiters
$template->render(['user-name' => 'johnny']); // Hello, johnny! 
```

#### One delimiter with factory
Often times there will be some kind of convention within your application
with regard to delimiters and the escape character. This these cases,
it often useful to be able to instantiate several templates based on that standard,
without having to specify it every time. The factory can represent that convention.
This example uses tokens with only the left delimiter.

```php
use Dhii\Output\DelimitedTokenTemplate\TemplateFactory;

$factory= new TemplateFactory(':', '', ''); // Note absence of right delimiter and escape character
$profilePath = $factory->fromString('/users/:username/profile')
    ->render(['username' => 'johnny']); // '/users/johnny/profile'
$settingsPath = $factory->fromString('/users/:userId/settings')
    ->render(['userId' => '1234']); // '/users/1234/settings'
```
