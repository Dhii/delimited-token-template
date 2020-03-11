<?php

namespace functional;

use Dhii\Output\DelimitedTokenTemplate\Template;
use Dhii\Output\DelimitedTokenTemplate\TemplateFactory as TestSubject;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TemplateFactoryTest extends TestCase
{
    /**
     * @param string $leftDelim
     * @param string $rightDelim
     * @param string $escapeChar
     *
     * @return TestSubject&MockObject
     */
    public function createInstance(
        string $leftDelim,
        string $rightDelim,
        string $escapeChar
    ): TestSubject {
        $mock = $this->getMockBuilder(TestSubject::class)
            ->setMethods(null)
            ->setConstructorArgs([$leftDelim, $rightDelim, $escapeChar])
            ->getMock();

        return $mock;
    }

    public function testFromString()
    {
        {
            $leftDelim = ':';
            $rightDelim = '';
            $escapeChar = '\\';
            $name = 'Johnny';
            $templateString = 'Good night, :name!';
            $subject = $this->createInstance($leftDelim, $rightDelim, $escapeChar);
        }

        {
            $template = $subject->fromString($templateString);
            $this->assertInstanceOf(Template::class, $template);

            $result = $template->render(['name' => $name]);
            $this->assertEquals("Good night, $name!", $result);
        }
    }
}
