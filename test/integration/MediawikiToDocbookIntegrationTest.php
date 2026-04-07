<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Test\Integration;

use Horde\Text\Wiki\Mediawiki\MediawikiParser;
use Horde\Text\Wiki\Renderer\Docbook;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests: MediaWiki parser → DocBook renderer
 *
 * Verifies that MediaWiki wikitext is correctly parsed into AST
 * and rendered to DocBook XML for all supported token types.
 *
 * @coversNothing
 */
class MediawikiToDocbookIntegrationTest extends TestCase
{
    private MediawikiParser $parser;
    private Docbook $renderer;

    protected function setUp(): void
    {
        $this->parser = new MediawikiParser();
        $this->renderer = new Docbook();
    }

    private function render(string $input): string
    {
        return $this->renderer->render($this->parser->parse($input));
    }

    // ---------------------------------------------------------------
    // Document structure
    // ---------------------------------------------------------------

    public function testDocumentWrapper(): void
    {
        $result = $this->render('Hello world');
        $this->assertStringContainsString('<?xml version="1.0"', $result);
        $this->assertStringContainsString('<article', $result);
        $this->assertStringContainsString('</article>', $result);
        $this->assertStringContainsString('Hello world', $result);
    }

    // ---------------------------------------------------------------
    // Inline formatting
    // ---------------------------------------------------------------

    public function testBold(): void
    {
        $result = $this->render("'''bold text'''");
        $this->assertStringContainsString('<emphasis role="strong">bold text</emphasis>', $result);
    }

    public function testItalic(): void
    {
        $result = $this->render("''italic text''");
        $this->assertStringContainsString('<emphasis>italic text</emphasis>', $result);
    }

    public function testUnderline(): void
    {
        $result = $this->render('<u>underlined</u>');
        $this->assertStringContainsString('<emphasis role="underline">underlined</emphasis>', $result);
    }

    public function testMonospace(): void
    {
        $result = $this->render('<tt>monospace</tt>');
        $this->assertStringContainsString('<code>monospace</code>', $result);
    }

    public function testSuperscript(): void
    {
        $result = $this->render('E=mc<sup>2</sup>');
        $this->assertStringContainsString('<superscript>2</superscript>', $result);
    }

    public function testSubscript(): void
    {
        $result = $this->render('H<sub>2</sub>O');
        $this->assertStringContainsString('<subscript>2</subscript>', $result);
    }

    public function testStrikethrough(): void
    {
        $result = $this->render('<s>struck</s>');
        $this->assertStringContainsString('<emphasis role="strikethrough">struck</emphasis>', $result);
    }

    public function testDel(): void
    {
        $result = $this->render('<del>deleted</del>');
        $this->assertStringContainsString('<emphasis role="deleted">deleted</emphasis>', $result);
    }

    public function testIns(): void
    {
        $result = $this->render('<ins>inserted</ins>');
        $this->assertStringContainsString('<emphasis role="inserted">inserted</emphasis>', $result);
    }

    // ---------------------------------------------------------------
    // Headings
    // ---------------------------------------------------------------

    public function testHeading(): void
    {
        $result = $this->render('== Section Title ==');
        $this->assertStringContainsString('<section>', $result);
        $this->assertStringContainsString('<title>Section Title</title>', $result);
    }

    public function testNestedHeadings(): void
    {
        $result = $this->render("== Section ==\n\n=== Subsection ===");
        // Both should create sections
        $this->assertStringContainsString('<section>', $result);
        $this->assertStringContainsString('<title>Section</title>', $result);
        $this->assertStringContainsString('<title>Subsection</title>', $result);
    }

    // ---------------------------------------------------------------
    // Links
    // ---------------------------------------------------------------

    public function testUrlDescribed(): void
    {
        $result = $this->render('[http://example.com Example Site]');
        $this->assertStringContainsString('xlink:href="http://example.com"', $result);
        $this->assertStringContainsString('Example Site</link>', $result);
    }

    public function testEmail(): void
    {
        $result = $this->render('[[mailto:user@example.com|Email me]]');
        $this->assertStringContainsString('mailto:user@example.com', $result);
        $this->assertStringContainsString('Email me', $result);
    }

    // ---------------------------------------------------------------
    // Block elements
    // ---------------------------------------------------------------

    public function testCodeBlock(): void
    {
        $result = $this->render("<code>\necho hello\n</code>");
        $this->assertStringContainsString('<programlisting>', $result);
        $this->assertStringContainsString('echo hello', $result);
        $this->assertStringContainsString('</programlisting>', $result);
    }

    public function testBlockquote(): void
    {
        $result = $this->render('<blockquote>quoted text</blockquote>');
        $this->assertStringContainsString('<blockquote>', $result);
        $this->assertStringContainsString('quoted text', $result);
        $this->assertStringContainsString('</blockquote>', $result);
    }

    // ---------------------------------------------------------------
    // Lists
    // ---------------------------------------------------------------

    public function testBulletList(): void
    {
        $result = $this->render("* Item 1\n* Item 2\n* Item 3");
        $this->assertStringContainsString('<itemizedlist>', $result);
        $this->assertStringContainsString('<listitem>', $result);
        $this->assertStringContainsString('Item 1', $result);
        $this->assertStringContainsString('</itemizedlist>', $result);
    }

    public function testNumberedList(): void
    {
        $result = $this->render("# First\n# Second\n# Third");
        $this->assertStringContainsString('<orderedlist>', $result);
        $this->assertStringContainsString('<listitem>', $result);
        $this->assertStringContainsString('</orderedlist>', $result);
    }

    // ---------------------------------------------------------------
    // Tables
    // ---------------------------------------------------------------

    public function testSimpleTable(): void
    {
        $input = "{|\n! Header 1 !! Header 2\n|-\n| Cell 1 || Cell 2\n|}";
        $result = $this->render($input);
        $this->assertStringContainsString('<informaltable>', $result);
        $this->assertStringContainsString('Header 1', $result);
        $this->assertStringContainsString('Cell 1', $result);
        $this->assertStringContainsString('</informaltable>', $result);
    }

    // ---------------------------------------------------------------
    // Definition lists
    // ---------------------------------------------------------------

    public function testDeflist(): void
    {
        $result = $this->render("; Term : Definition");
        $this->assertStringContainsString('<glosslist>', $result);
        $this->assertStringContainsString('<glossterm>', $result);
        $this->assertStringContainsString('Term', $result);
        $this->assertStringContainsString('<glossdef>', $result);
        $this->assertStringContainsString('Definition', $result);
        $this->assertStringContainsString('</glosslist>', $result);
    }

    // ---------------------------------------------------------------
    // XML escaping
    // ---------------------------------------------------------------

    public function testXmlEscaping(): void
    {
        $result = $this->render('A < B & C > D');
        $this->assertStringContainsString('&lt;', $result);
        $this->assertStringContainsString('&amp;', $result);
    }

    // ---------------------------------------------------------------
    // Complex document
    // ---------------------------------------------------------------

    public function testComplexDocument(): void
    {
        $source = <<<'WIKI'
== Main Heading ==

Some paragraph with '''bold''' text.

* Item 1
* Item 2

<blockquote>A famous quote</blockquote>
WIKI;

        $result = $this->render($source);
        $this->assertStringContainsString('<article', $result);
        $this->assertStringContainsString('<section>', $result);
        $this->assertStringContainsString('<title>Main Heading</title>', $result);
        $this->assertStringContainsString('<emphasis role="strong">bold</emphasis>', $result);
        $this->assertStringContainsString('<itemizedlist>', $result);
        $this->assertStringContainsString('<blockquote>', $result);
        $this->assertStringContainsString('</article>', $result);
    }

    // ---------------------------------------------------------------
    // Format
    // ---------------------------------------------------------------

    public function testGetFormat(): void
    {
        $this->assertSame('docbook', $this->renderer->getFormat());
    }
}
