<?php

declare(strict_types=1);

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\CowikiEngine;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Test Cowiki heading parsing (+ Heading)
 *
 * Cowiki uses plus signs for headings, similar to Default dialect
 */
#[CoversClass(CowikiEngine::class)]
class CowikiParseHeadingTest extends TestCase
{
    private CowikiEngine $wiki;

    protected function setUp(): void
    {
        $this->wiki = new CowikiEngine();
    }

    public function testHeadingLevel1(): void
    {
        $source = '+ Heading Level 1';
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<h1', $result);
        $this->assertStringContainsString('Heading Level 1', $result);
        $this->assertStringContainsString('</h1>', $result);
    }

    public function testHeadingLevel2(): void
    {
        $source = '++ Heading Level 2';
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<h2', $result);
        $this->assertStringContainsString('Heading Level 2', $result);
        $this->assertStringContainsString('</h2>', $result);
    }

    public function testHeadingLevel3(): void
    {
        $source = '+++ Heading Level 3';
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<h3', $result);
        $this->assertStringContainsString('Heading Level 3', $result);
        $this->assertStringContainsString('</h3>', $result);
    }

    public function testHeadingLevel4(): void
    {
        $source = '++++ Heading Level 4';
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<h4', $result);
        $this->assertStringContainsString('Heading Level 4', $result);
        $this->assertStringContainsString('</h4>', $result);
    }

    public function testMultipleHeadings(): void
    {
        $source = "+ First Heading\n\nSome text\n\n++ Second Heading";
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<h1', $result);
        $this->assertStringContainsString('First Heading', $result);
        $this->assertStringContainsString('<h2', $result);
        $this->assertStringContainsString('Second Heading', $result);
    }

    public function testHeadingWithId(): void
    {
        $source = '+ Test Heading';
        $result = $this->wiki->transform($source, 'Xhtml');

        // Headings should have IDs for TOC linking
        $this->assertStringContainsString('id=', $result);
        $this->assertStringContainsString('toc', $result);
    }

    public function testHeadingWithFormatting(): void
    {
        $source = '+ Heading with *bold* text';
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<h1', $result);
        // Bold should be processed within heading
        $this->assertStringContainsString('Heading with', $result);
        $this->assertStringContainsString('bold', $result);
    }

    public function testHeadingNotAtStartOfLine(): void
    {
        $source = 'Text + Not A Heading';
        $result = $this->wiki->transform($source, 'Xhtml');

        // Should NOT create heading (plus must be at line start)
        $this->assertStringNotContainsString('<h1>', $result);
        $this->assertStringContainsString('+', $result);
    }

    public function testHeadingHierarchy(): void
    {
        $source = "+ Level 1\n++ Level 2\n+++ Level 3\n++ Back to Level 2";
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<h1', $result);
        $this->assertStringContainsString('Level 1', $result);
        $this->assertStringContainsString('<h2', $result);
        $this->assertStringContainsString('<h3', $result);
        $this->assertStringContainsString('Level 3', $result);
    }

    public function testHeadingRenderToPlain(): void
    {
        $source = '+ Heading Text';
        $result = $this->wiki->transform($source, 'Plain');

        // Plain text should preserve heading content
        $this->assertStringContainsString('Heading Text', $result);
        $this->assertStringNotContainsString('<h1>', $result);
    }
}
