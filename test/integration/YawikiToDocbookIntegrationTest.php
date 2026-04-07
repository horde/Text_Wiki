<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Test\Integration;

use Horde\Text\Wiki\Yawiki\YawikiParser;
use Horde\Text\Wiki\Renderer\Docbook;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests: Yawiki parser → DocBook renderer
 *
 * Verifies that Yawiki wikitext is correctly parsed into AST
 * and rendered to DocBook XML for all supported token types.
 *
 * @coversNothing
 */
class YawikiToDocbookIntegrationTest extends TestCase
{
    private YawikiParser $parser;
    private Docbook $renderer;

    protected function setUp(): void
    {
        $this->parser = new YawikiParser();
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
        $this->assertStringContainsString('<emphasis role="bold">bold text</emphasis>', $result);
    }

    public function testItalic(): void
    {
        $result = $this->render("''italic text''");
        $this->assertStringContainsString('<emphasis>italic text</emphasis>', $result);
    }

    public function testStrong(): void
    {
        $result = $this->render('**strong text**');
        $this->assertStringContainsString('<emphasis role="strong">strong text</emphasis>', $result);
    }

    public function testEmphasis(): void
    {
        $result = $this->render('//emphasis text//');
        $this->assertStringContainsString('<emphasis>emphasis text</emphasis>', $result);
    }

    public function testUnderline(): void
    {
        $result = $this->render('__underlined__');
        $this->assertStringContainsString('<emphasis role="underline">underlined</emphasis>', $result);
    }

    public function testMonospace(): void
    {
        $result = $this->render('{{monospace}}');
        $this->assertStringContainsString('<code>monospace</code>', $result);
    }

    public function testSuperscript(): void
    {
        $result = $this->render('E=mc^^2^^');
        $this->assertStringContainsString('<superscript>2</superscript>', $result);
    }

    public function testSubscript(): void
    {
        $result = $this->render('H,,2,,O');
        $this->assertStringContainsString('<subscript>2</subscript>', $result);
    }

    // ---------------------------------------------------------------
    // Headings
    // ---------------------------------------------------------------

    public function testHeading(): void
    {
        $result = $this->render('++ Section Title');
        $this->assertStringContainsString('<section>', $result);
        $this->assertStringContainsString('<title>Section Title</title>', $result);
    }

    public function testNestedHeadings(): void
    {
        $result = $this->render("++ Section\n\n+++ Subsection");
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

    public function testFreelink(): void
    {
        $result = $this->render('((Free Link))');
        $this->assertStringContainsString('Free Link', $result);
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
        $result = $this->render('> quoted text');
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
        $input = "|| ~ Header 1 || ~ Header 2 ||\n|| Cell 1 || Cell 2 ||";
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
        $result = $this->render(': Term : Definition');
        $this->assertStringContainsString('<glosslist>', $result);
        $this->assertStringContainsString('<glossterm>', $result);
        $this->assertStringContainsString('Term', $result);
        $this->assertStringContainsString('<glossdef>', $result);
        $this->assertStringContainsString('Definition', $result);
        $this->assertStringContainsString('</glosslist>', $result);
    }

    // ---------------------------------------------------------------
    // Colortext
    // ---------------------------------------------------------------

    public function testColortext(): void
    {
        $result = $this->render('##FF0000|Red text##');
        $this->assertStringContainsString('Red text', $result);
    }

    // ---------------------------------------------------------------
    // Revise marks
    // ---------------------------------------------------------------

    public function testReviseDel(): void
    {
        $result = $this->render('@@---deleted text@@');
        $this->assertStringContainsString('<emphasis role="deleted">deleted text</emphasis>', $result);
    }

    public function testReviseIns(): void
    {
        $result = $this->render('@@+++inserted text@@');
        $this->assertStringContainsString('<emphasis role="inserted">inserted text</emphasis>', $result);
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
++ Main Heading

Some paragraph with '''bold''' text.

* Item 1
* Item 2

> A famous quote
WIKI;

        $result = $this->render($source);
        $this->assertStringContainsString('<article', $result);
        $this->assertStringContainsString('<section>', $result);
        $this->assertStringContainsString('<title>Main Heading</title>', $result);
        $this->assertStringContainsString('<emphasis role="bold">bold</emphasis>', $result);
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
