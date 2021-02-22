<?php

namespace Dhii\Output\DelimitedTokenTemplate\Test\Func;

use Dhii\Output\DelimitedTokenTemplate\Template as TestSubject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RangeException;
use stdClass;
use Stringable;

class TemplateTest extends TestCase
{
    /**
     * @return TestSubject|MockObject
     */
    public function createInstance(string $template, string $leftDelimiter, string $rightDelimiter, string $escapeChar): TestSubject
    {
        $mock = $this->getMockBuilder(TestSubject::class)
            ->setMethods(null)
            ->setConstructorArgs([$template, $leftDelimiter, $rightDelimiter, $escapeChar])
            ->getMock();

        return $mock;
    }

    /**
     * Tests that a template renders correctly with both delimiters (asymmetric), including escape sequences.
     */
    public function testRenderBothDelimiters()
    {
        {
            $name = 'Mary';
            $adjective = 'little';
            $word = 'great';
            $template = '{{name}}} had a {{adjective}}} {{animal}}}. {{name}}} was very happy; :\{{ {{\{{word}\}}}}!';
            $escapeChar = '\\';
            $leftDelimiter = '{{';
            $rightDelimiter = '}}}';
            $subject = $this->createInstance($template, $leftDelimiter, $rightDelimiter, $escapeChar);
        }

        {
            $result = $subject->render([
                'name' => $name,
                'adjective' => $adjective,
                '{{word}\}' => $word,
            ]);

            $this->assertEquals("{$name} had a $adjective {{animal}}}. $name was very happy; :{{ $word!", $result);
        }
    }

    /**
     * Tests that the template can render correctly with only the left delimiter present.
     */
    public function testRenderLeftDelimiter()
    {
        {
            $leftDelim = ':';
            $rightDelim = '';
            $escapeChar = '';
            $key = 'name';
            $value = 'Johnny';
            $templateString = "Good night, :$key!";
            $subject = $this->createInstance($templateString, $leftDelim, $rightDelim, $escapeChar);
        }

        {
            $result = $subject->render([$key => $value]);
            $this->assertEquals("Good night, $value!", $result);
        }
    }

    /**
     * Tests that the template can render correctly with only the right delimiter present.
     */
    public function testRenderRightDelimiter()
    {
        {
            $leftDelim = '';
            $rightDelim = '+';
            $escapeChar = '';
            $key = 'name';
            $value = 'Johnny';
            $templateString = "Good night, $key+!";
            $subject = $this->createInstance($templateString, $leftDelim, $rightDelim, $escapeChar);
        }

        {
            $result = $subject->render([$key => $value]);
            $this->assertEquals("Good night, $value!", $result);
        }
    }

    /**
     * Tests that rendering happens correctly when the token name has a delimiter in the middle.
     */
    public function testRenderDelimiterInMiddle()
    {
        {
            $leftDelim = '%';
            $rightDelim = '%';
            $escapeChar = '\\';
            $key = 'user%name';
            $value = 'Johnny';
            $templateString = "Good night, %user\%name%!";
            $subject = $this->createInstance($templateString, $leftDelim, $rightDelim, $escapeChar);
        }

        {
            $result = $subject->render([$key => $value]);
            $this->assertEquals("Good night, $value!", $result);
        }
    }

    /**
     * Tests that values of allowed types may be used, and disallowed throw.
     *
     * @dataProvider provideContext
     * @param mixed $context The context to render with.
     * @param mixed $expect What to expect.
     * @param string|null $shouldExpectException The exception type to expect, if any.
     */
    public function testValueTypes($context, $expect, ?string $shouldExpectException)
    {
        {
            $e = '\\';
            $d = '%';
            $key = 'key';
            $template = "{$d}{$key}{$d}";
            $subject = $this->createInstance($template, $d, $d, $e);
        }

        {
            if ($shouldExpectException) {
                $this->expectException($shouldExpectException);
            }

            $result = $subject->render($context);
            $this->assertEquals($expect, $result);
        }
    }

    /**
     * @return array[]
     */
    public function provideContext(): array
    {
        return [
            [ // Set
                ['key' => $value = uniqid('value1')], // Context
                $value, // Expect
                null, // Should throw
            ],

            [ // Set
                ['key' => $value = rand(1, 99)], // Context
                $value, // Expect
                null, // Should throw
            ],

            [ // Set
                ['key' => $value = rand(0, 100) < 50], // Context
                (string) (int) $value, // Expect
                null, // Should throw
            ],

            [ // Set
                ['key' => $value = $this->createStringable(uniqid('value2'))], // Context
                $value, // Expect
                null, // Should throw
            ],

            [ // Set
                ['key' => new stdClass()], // Context
                null, // Expect
                RangeException::class, // Should throw
            ],
        ];
    }

    /**
     * Creates a new stringable that represents the specified content.
     *
     * @param string $content The content of the stringable.
     *
     * @return Stringable|MockObject The new stringable.
     */
    protected function createStringable(string $content): Stringable
    {
        $mock = $this->getMockBuilder(Stringable::class)
            ->setMethods(['__toString'])
            ->getMockForAbstractClass();

        $mock->method('__toString')
            ->will($this->returnValue($content));

        return $mock;
    }
}
