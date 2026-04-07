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
use Horde\Text\Wiki\Renderer\Latex;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests: BBCode parser → LaTeX renderer
 *
 * Verifies that BBCode input is correctly parsed into AST
 * and rendered to LaTeX for all supported token types.
 *
 * @coversNothing
 */
class BbcodeToLatexIntegrationTest extends TestCase
{
    private BBCodeParser $parser;
    private Latex $renderer;

    protected function setUp(): void
    {
        $this->parser = new BBCodeParser();
        $this->renderer = new Latex();
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
        $this->assertStringContainsString('\\documentclass{article}', $result);
        $this->assertStringContainsString('\\begin{document}', $result);
        $this->assertStringContainsString('\\end{document}', $result);
        $this->assertStringContainsString('Hello', $result);
    }

    // ---------------------------------------------------------------
    // Inline formatting
    // ---------------------------------------------------------------

    public function testBold(): void
    {
        $result = $this->render('[b]bold text[/b]');
        $this->assertStringContainsString('\\textbf{bold text}', $result);
    }

    public function testItalic(): void
    {
        $result = $this->render('[i]italic text[/i]');
        $this->assertStringContainsString('\\textit{italic text}', $result);
    }

    public function testUnderline(): void
    {
        $result = $this->render('[u]underlined[/u]');
        $this->assertStringContainsString('\\underline{underlined}', $result);
    }

    public function testStrike(): void
    {
        $result = $this->render('[s]struck[/s]');
        $this->assertStringContainsString('\\sout{struck}', $result);
    }

    // ---------------------------------------------------------------
    // Links
    // ---------------------------------------------------------------

    public function testUrlWithText(): void
    {
        $result = $this->render('[url=http://example.com]Click here[/url]');
        $this->assertStringContainsString('Click here', $result);
        $this->assertStringContainsString('\\footnote{', $result);
    }

    public function testUrlBare(): void
    {
        $result = $this->render('[url]http://example.com[/url]');
        $this->assertStringContainsString('\\url{http://example.com}', $result);
    }

    // ---------------------------------------------------------------
    // Code
    // ---------------------------------------------------------------

    public function testCode(): void
    {
        $result = $this->render('[code]echo hello[/code]');
        $this->assertStringContainsString('\\begin{verbatim}', $result);
        $this->assertStringContainsString('echo hello', $result);
        $this->assertStringContainsString('\\end{verbatim}', $result);
    }

    // ---------------------------------------------------------------
    // Lists
    // ---------------------------------------------------------------

    public function testUnorderedList(): void
    {
        $result = $this->render("[list]\n[*]Item 1\n[*]Item 2\n[/list]");
        $this->assertStringContainsString('\\begin{itemize}', $result);
        $this->assertStringContainsString('\\item', $result);
        $this->assertStringContainsString('\\end{itemize}', $result);
    }

    public function testOrderedList(): void
    {
        $result = $this->render("[list=1]\n[*]First\n[*]Second\n[/list]");
        $this->assertStringContainsString('\\begin{enumerate}', $result);
        $this->assertStringContainsString('\\item', $result);
        $this->assertStringContainsString('\\end{enumerate}', $result);
    }

    // ---------------------------------------------------------------
    // Blockquote
    // ---------------------------------------------------------------

    public function testBlockquote(): void
    {
        $result = $this->render('[quote]A famous quote[/quote]');
        $this->assertStringContainsString('\\begin{quote}', $result);
        $this->assertStringContainsString('A famous quote', $result);
        $this->assertStringContainsString('\\end{quote}', $result);
    }

    // ---------------------------------------------------------------
    // Color / Size (pass through)
    // ---------------------------------------------------------------

    public function testColorPassthrough(): void
    {
        $result = $this->render('[color=#FF0000]Red text[/color]');
        $this->assertStringContainsString('Red text', $result);
    }

    public function testSizePassthrough(): void
    {
        $result = $this->render('[size=14]Big text[/size]');
        $this->assertStringContainsString('Big text', $result);
    }

    // ---------------------------------------------------------------
    // Special characters escape
    // ---------------------------------------------------------------

    public function testSpecialCharactersEscaped(): void
    {
        $result = $this->render('Price: $100 & 50%');
        $this->assertStringContainsString('\\$', $result);
        $this->assertStringContainsString('\\&', $result);
        $this->assertStringContainsString('\\%', $result);
    }

    // ---------------------------------------------------------------
    // Format
    // ---------------------------------------------------------------

    public function testGetFormat(): void
    {
        $this->assertSame('latex', $this->renderer->getFormat());
    }
}
