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
     * Tests that a template renders correctly with both delimiters, including escape sequences.
     */
    public function testRenderBothDelimiters()
    {
        {
            $name = 'Mary';
            $adjective = 'little';
            $word = 'great';
            $template = '{{name}} had a {{adjective}} {{animal}}. {{name}} was very happy; :\{{ {{\{{word}\}}}!';
            $escapeChar = '\\';
            $leftDelimiter = '{{';
            $rightDelimiter = '}}';
            $subject = $this->createInstance($template, $leftDelimiter, $rightDelimiter, $escapeChar);
        }

        {
            $result = $subject->render([
                'name' => $name,
                'adjective' => $adjective,
                '{{word}\}' => $word,
            ]);

            $this->assertEquals("{$name} had a $adjective {{animal}}. $name was very happy; :{{ $word!", $result);
        }
    }
}
