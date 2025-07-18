<?php

use PHPUnit\Framework\TestCase;
use WikiConnect\ParseWiki\ParserSection;
use WikiConnect\ParseWiki\DataModel\Section;

class ParserSectionTest extends TestCase
{
    public function testSingleSection()
    {
        $text = "Intro\n\n== Section 1 ==\nContent of section 1.";
        $parser = new ParserSection($text);
        $sections = $parser->getSections();

        $this->assertCount(1, $sections);
        $this->assertInstanceOf(Section::class, $sections[0]);
        $this->assertEquals(2, $sections[0]->getLevel());
        $this->assertEquals('Section 1', $sections[0]->getTitle());
        $this->assertEquals('Content of section 1.', $sections[0]->getContent());
    }

    public function testMultipleSections()
    {
        $text = "== First ==\nFirst content.\n=== Subsection ===\nSubsection content.\n== Second ==\nSecond content.";
        $parser = new ParserSection($text);
        $sections = $parser->getSections();

        $this->assertCount(3, $sections);
        $this->assertEquals(2, $sections[0]->getLevel());
        $this->assertEquals('First', $sections[0]->getTitle());
        $this->assertEquals('First content.', $sections[0]->getContent());

        $this->assertEquals(3, $sections[1]->getLevel());
        $this->assertEquals('Subsection', $sections[1]->getTitle());
        $this->assertEquals('Subsection content.', $sections[1]->getContent());

        $this->assertEquals(2, $sections[2]->getLevel());
        $this->assertEquals('Second', $sections[2]->getTitle());
        $this->assertEquals('Second content.', $sections[2]->getContent());
    }

    public function testNoSections()
    {
        $text = "This is just plain text.\nNo sections here.";
        $parser = new ParserSection($text);
        $sections = $parser->getSections();

        $this->assertIsArray($sections);
        $this->assertCount(0, $sections);
    }

    public function testSectionWithWhitespaceInTitle()
    {
        $text = "==   Section Title   ==\nContent.";
        $parser = new ParserSection($text);
        $sections = $parser->getSections();

        $this->assertCount(1, $sections);
        $this->assertEquals('Section Title', $sections[0]->getTitle());
    }

    public function testAdjacentSections()
    {
        $text = "== A ==\nContent A\n== B ==\nContent B";
        $parser = new ParserSection($text);
        $sections = $parser->getSections();

        $this->assertCount(2, $sections);
        $this->assertEquals('A', $sections[0]->getTitle());
        $this->assertEquals('Content A', $sections[0]->getContent());
        $this->assertEquals('B', $sections[1]->getTitle());
        $this->assertEquals('Content B', $sections[1]->getContent());
    }

    public function testSectionWithEmptyContent()
    {
        $text = "== Empty ==\n== Not Empty ==\nSome content";
        $parser = new ParserSection($text);
        $sections = $parser->getSections();

        $this->assertCount(2, $sections);
        $this->assertEquals('Empty', $sections[0]->getTitle());
        $this->assertEquals('', $sections[0]->getContent());
        $this->assertEquals('Not Empty', $sections[1]->getTitle());
        $this->assertEquals('Some content', $sections[1]->getContent());
    }

    public function testSectionLevels()
    {
        $text = "== Level2 ==\nContent2\n=== Level3 ===\nContent3\n==== Level4 ====\nContent4";
        $parser = new ParserSection($text);
        $sections = $parser->getSections();

        $this->assertCount(3, $sections);
        $this->assertEquals(2, $sections[0]->getLevel());
        $this->assertEquals(3, $sections[1]->getLevel());
        $this->assertEquals(4, $sections[2]->getLevel());
    }
}