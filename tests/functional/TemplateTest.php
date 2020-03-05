<?php

namespace Dhii\Output\DelimitedTokenTemplate\Test\Func;

use Dhii\Output\DelimitedTokenTemplate\Template as TestSubject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
}
