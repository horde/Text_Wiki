<?php

declare(strict_types=1);

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use Horde\Text\Wiki\CowikiParserHeading;
use Horde\Text\Wiki\XhtmlRendererHeading;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Test Cowiki heading parsing (+ Heading)
 *
 * Cowiki uses plus signs for headings similar to Default dialect
 */
#[CoversClass(CowikiParserHeading::class)]
#[CoversClass(XhtmlRendererHeading::class)]
class CowikiParseHeadingTest extends TestCase
{
    public function testHeadingLevel1(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Heading']);
        $source = '+ Heading Level 1';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<h1', $result);
        $this->assertStringContainsString('Heading Level 1', $result);
        $this->assertStringContainsString('</h1>', $result);
    }

    public function testHeadingLevel2(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Heading']);
        $source = '++ Heading Level 2';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<h2', $result);
        $this->assertStringContainsString('Heading Level 2', $result);
        $this->assertStringContainsString('</h2>', $result);
    }

    public function testHeadingLevel3(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Heading']);
        $source = '+++ Heading Level 3';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<h3', $result);
        $this->assertStringContainsString('Heading Level 3', $result);
        $this->assertStringContainsString('</h3>', $result);
    }

    public function testHeadingLevel4(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Heading']);
        $source = '++++ Heading Level 4';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<h4', $result);
        $this->assertStringContainsString('Heading Level 4', $result);
        $this->assertStringContainsString('</h4>', $result);
    }

    public function testMultipleHeadings(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Heading', 'Paragraph']);
        $source = "+ First Heading\n\nSome text\n\n++ Second Heading";
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<h1', $result);
        $this->assertStringContainsString('First Heading', $result);
        $this->assertStringContainsString('<h2', $result);
        $this->assertStringContainsString('Second Heading', $result);
    }

    public function testHeadingWithId(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Heading']);
        $source = '+ Test Heading';
        $result = $wiki->transform($source, 'Xhtml');

        // Headings should have IDs for TOC linking
        $this->assertStringContainsString('id=', $result);
        $this->assertStringContainsString('toc', $result);
    }

    public function testHeadingWithFormatting(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Heading', 'Bold']);
        $source = '+ Heading with *bold* text';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<h1', $result);
        // Bold should be processed within heading
        $this->assertStringContainsString('Heading with', $result);
        $this->assertStringContainsString('bold', $result);
    }

    public function testHeadingNotAtStartOfLine(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Heading']);
        $source = 'Text + Not A Heading';
        $result = $wiki->transform($source, 'Xhtml');

        // Should NOT create heading (plus must be at line start)
        $this->assertStringNotContainsString('<h1>', $result);
        $this->assertStringContainsString('+', $result);
    }

    public function testHeadingHierarchy(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Heading']);
        $source = "+ Level 1\n++ Level 2\n+++ Level 3\n++ Back to Level 2";
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<h1', $result);
        $this->assertStringContainsString('Level 1', $result);
        $this->assertStringContainsString('<h2', $result);
        $this->assertStringContainsString('<h3', $result);
        $this->assertStringContainsString('Level 3', $result);
    }

    public function testHeadingRenderToPlain(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Heading']);
        $source = '+ Heading Text';
        $result = $wiki->transform($source, 'Plain');

        // Plain text should preserve heading content
        $this->assertStringContainsString('Heading Text', $result);
        $this->assertStringNotContainsString('<h1>', $result);
    }
}
