<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Test\Integration;

use Horde\Text\Wiki\BBCode\BBCodeParser;
use Horde\Text\Wiki\Renderer\Docbook;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests: BBCode parser → DocBook renderer
 *
 * Verifies that BBCode input is correctly parsed into AST
 * and rendered to DocBook XML for all supported token types.
 *
 * @coversNothing
 */
class BbcodeToDocbookIntegrationTest extends TestCase
{
    private BBCodeParser $parser;
    private Docbook $renderer;

    protected function setUp(): void
    {
        $this->parser = new BBCodeParser();
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
        $result = $this->render('Hello');
        $this->assertStringContainsString('<?xml version="1.0"', $result);
        $this->assertStringContainsString('<article', $result);
        $this->assertStringContainsString('</article>', $result);
        $this->assertStringContainsString('Hello', $result);
    }

    // ---------------------------------------------------------------
    // Inline formatting
    // ---------------------------------------------------------------

    public function testBold(): void
    {
        $result = $this->render('[b]bold text[/b]');
        $this->assertStringContainsString('<emphasis role="bold">bold text</emphasis>', $result);
    }

    public function testItalic(): void
    {
        $result = $this->render('[i]italic text[/i]');
        $this->assertStringContainsString('<emphasis>italic text</emphasis>', $result);
    }

    public function testUnderline(): void
    {
        $result = $this->render('[u]underlined[/u]');
        $this->assertStringContainsString('<emphasis role="underline">underlined</emphasis>', $result);
    }

    public function testStrike(): void
    {
        $result = $this->render('[s]struck[/s]');
        $this->assertStringContainsString('<emphasis role="strikethrough">struck</emphasis>', $result);
    }

    // ---------------------------------------------------------------
    // Links
    // ---------------------------------------------------------------

    public function testUrlWithText(): void
    {
        $result = $this->render('[url=http://example.com]Click here[/url]');
        $this->assertStringContainsString('xlink:href="http://example.com"', $result);
        $this->assertStringContainsString('Click here</link>', $result);
    }

    // ---------------------------------------------------------------
    // Code
    // ---------------------------------------------------------------

    public function testCode(): void
    {
        $result = $this->render('[code]echo hello[/code]');
        $this->assertStringContainsString('<programlisting>', $result);
        $this->assertStringContainsString('echo hello', $result);
        $this->assertStringContainsString('</programlisting>', $result);
    }

    // ---------------------------------------------------------------
    // Lists
    // ---------------------------------------------------------------

    public function testUnorderedList(): void
    {
        $result = $this->render("[list]\n[*]Item 1\n[*]Item 2\n[/list]");
        $this->assertStringContainsString('<itemizedlist>', $result);
        $this->assertStringContainsString('<listitem>', $result);
        $this->assertStringContainsString('</itemizedlist>', $result);
    }

    public function testOrderedList(): void
    {
        $result = $this->render("[list=1]\n[*]First\n[*]Second\n[/list]");
        $this->assertStringContainsString('<orderedlist>', $result);
        $this->assertStringContainsString('<listitem>', $result);
        $this->assertStringContainsString('</orderedlist>', $result);
    }

    // ---------------------------------------------------------------
    // Blockquote
    // ---------------------------------------------------------------

    public function testBlockquote(): void
    {
        $result = $this->render('[quote]A famous quote[/quote]');
        $this->assertStringContainsString('<blockquote>', $result);
        $this->assertStringContainsString('A famous quote', $result);
        $this->assertStringContainsString('</blockquote>', $result);
    }

    // ---------------------------------------------------------------
    // Color
    // ---------------------------------------------------------------

    public function testColor(): void
    {
        $result = $this->render('[color=#FF0000]Red text[/color]');
        $this->assertStringContainsString('Red text', $result);
    }

    // ---------------------------------------------------------------
    // XML escaping
    // ---------------------------------------------------------------

    public function testXmlEscaping(): void
    {
        $result = $this->render('A < B & C > D');
        $this->assertStringContainsString('&lt;', $result);
        $this->assertStringContainsString('&amp;', $result);
        $this->assertStringContainsString('&gt;', $result);
    }

    // ---------------------------------------------------------------
    // Format
    // ---------------------------------------------------------------

    public function testGetFormat(): void
    {
        $this->assertSame('docbook', $this->renderer->getFormat());
    }
}
