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
use Horde\Text\Wiki\Renderer\Latex;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests: MediaWiki parser → LaTeX renderer
 *
 * Verifies that MediaWiki wikitext is correctly parsed into AST
 * and rendered to LaTeX for all supported token types.
 *
 * @coversNothing
 */
class MediawikiToLatexIntegrationTest extends TestCase
{
    private MediawikiParser $parser;
    private Latex $renderer;

    protected function setUp(): void
    {
        $this->parser = new MediawikiParser();
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
        $result = $this->render('Hello world');
        $this->assertStringContainsString('\\documentclass{article}', $result);
        $this->assertStringContainsString('\\begin{document}', $result);
        $this->assertStringContainsString('\\end{document}', $result);
        $this->assertStringContainsString('Hello world', $result);
    }

    // ---------------------------------------------------------------
    // Inline formatting
    // ---------------------------------------------------------------

    public function testBold(): void
    {
        $result = $this->render("'''bold text'''");
        $this->assertStringContainsString('\\textbf{bold text}', $result);
    }

    public function testItalic(): void
    {
        $result = $this->render("''italic text''");
        $this->assertStringContainsString('\\textsl{italic text}', $result);
    }

    public function testUnderline(): void
    {
        $result = $this->render('<u>underlined</u>');
        $this->assertStringContainsString('\\underline{underlined}', $result);
    }

    public function testMonospace(): void
    {
        $result = $this->render('<tt>monospace</tt>');
        $this->assertStringContainsString('\\texttt{monospace}', $result);
    }

    public function testSuperscript(): void
    {
        $result = $this->render('E=mc<sup>2</sup>');
        $this->assertStringContainsString('\\textsuperscript{2}', $result);
    }

    public function testSubscript(): void
    {
        $result = $this->render('H<sub>2</sub>O');
        $this->assertStringContainsString('\\textsubscript{2}', $result);
    }

    public function testStrikethrough(): void
    {
        $result = $this->render('<s>struck</s>');
        $this->assertStringContainsString('\\sout{struck}', $result);
    }

    public function testDel(): void
    {
        $result = $this->render('<del>deleted</del>');
        $this->assertStringContainsString('\\sout{deleted}', $result);
    }

    public function testIns(): void
    {
        $result = $this->render('<ins>inserted</ins>');
        $this->assertStringContainsString('\\underline{inserted}', $result);
    }

    public function testBreak(): void
    {
        $result = $this->render('Line one<br />Line two');
        $this->assertStringContainsString('\\newline', $result);
    }

    // ---------------------------------------------------------------
    // Headings
    // ---------------------------------------------------------------

    public function testHeading1(): void
    {
        $result = $this->render('= Main Title =');
        $this->assertStringContainsString('\\part{Main Title}', $result);
    }

    public function testHeading2(): void
    {
        $result = $this->render('== Section ==');
        $this->assertStringContainsString('\\section{Section}', $result);
    }

    public function testHeading3(): void
    {
        $result = $this->render('=== Subsection ===');
        $this->assertStringContainsString('\\subsection{Subsection}', $result);
    }

    // ---------------------------------------------------------------
    // Links
    // ---------------------------------------------------------------

    public function testUrlDescribed(): void
    {
        $result = $this->render('[http://example.com Example Site]');
        $this->assertStringContainsString('Example Site', $result);
        $this->assertStringContainsString('\\footnote{', $result);
    }

    public function testWikilink(): void
    {
        $result = $this->render('[[Main Page|Go home]]');
        $this->assertStringContainsString('Go home', $result);
    }

    public function testEmail(): void
    {
        $result = $this->render('[[mailto:user@example.com|Email me]]');
        $this->assertStringContainsString('Email me', $result);
    }

    // ---------------------------------------------------------------
    // Block elements
    // ---------------------------------------------------------------

    public function testHorizontalRule(): void
    {
        $result = $this->render('----');
        $this->assertStringContainsString('\\rule{\\textwidth}{1pt}', $result);
    }

    public function testCodeBlock(): void
    {
        $result = $this->render("<code>\necho hello\n</code>");
        $this->assertStringContainsString('\\begin{verbatim}', $result);
        $this->assertStringContainsString('echo hello', $result);
        $this->assertStringContainsString('\\end{verbatim}', $result);
    }

    public function testBlockquote(): void
    {
        $result = $this->render('<blockquote>quoted text</blockquote>');
        $this->assertStringContainsString('\\begin{quote}', $result);
        $this->assertStringContainsString('quoted text', $result);
        $this->assertStringContainsString('\\end{quote}', $result);
    }

    // ---------------------------------------------------------------
    // Lists
    // ---------------------------------------------------------------

    public function testBulletList(): void
    {
        $result = $this->render("* Item 1\n* Item 2\n* Item 3");
        $this->assertStringContainsString('\\begin{itemize}', $result);
        $this->assertStringContainsString('\\item', $result);
        $this->assertStringContainsString('Item 1', $result);
        $this->assertStringContainsString('\\end{itemize}', $result);
    }

    public function testNumberedList(): void
    {
        $result = $this->render("# First\n# Second\n# Third");
        $this->assertStringContainsString('\\begin{enumerate}', $result);
        $this->assertStringContainsString('\\item', $result);
        $this->assertStringContainsString('\\end{enumerate}', $result);
    }

    // ---------------------------------------------------------------
    // Tables
    // ---------------------------------------------------------------

    public function testSimpleTable(): void
    {
        $input = "{|\n! Header 1 !! Header 2\n|-\n| Cell 1 || Cell 2\n|}";
        $result = $this->render($input);
        $this->assertStringContainsString('\\begin{tabular}', $result);
        $this->assertStringContainsString('Header 1', $result);
        $this->assertStringContainsString('Cell 1', $result);
        $this->assertStringContainsString('\\end{tabular}', $result);
    }

    // ---------------------------------------------------------------
    // Definition lists
    // ---------------------------------------------------------------

    public function testDeflist(): void
    {
        $result = $this->render("; Term : Definition");
        $this->assertStringContainsString('\\begin{description}', $result);
        $this->assertStringContainsString('\\item[', $result);
        $this->assertStringContainsString('Term', $result);
        $this->assertStringContainsString('Definition', $result);
        $this->assertStringContainsString('\\end{description}', $result);
    }

    // ---------------------------------------------------------------
    // TOC
    // ---------------------------------------------------------------

    public function testToc(): void
    {
        $result = $this->render('__TOC__');
        $this->assertStringContainsString('\\tableofcontents', $result);
    }

    // ---------------------------------------------------------------
    // Special characters
    // ---------------------------------------------------------------

    public function testSpecialCharactersEscaped(): void
    {
        $result = $this->render('Price: $100 & 50%');
        $this->assertStringContainsString('\\$', $result);
        $this->assertStringContainsString('\\&', $result);
        $this->assertStringContainsString('\\%', $result);
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

----

<blockquote>A famous quote</blockquote>
WIKI;

        $result = $this->render($source);
        $this->assertStringContainsString('\\documentclass', $result);
        $this->assertStringContainsString('\\section{Main Heading}', $result);
        $this->assertStringContainsString('\\textbf{bold}', $result);
        $this->assertStringContainsString('\\begin{itemize}', $result);
        $this->assertStringContainsString('\\rule{\\textwidth}{1pt}', $result);
        $this->assertStringContainsString('\\begin{quote}', $result);
        $this->assertStringContainsString('\\end{document}', $result);
    }

    // ---------------------------------------------------------------
    // Format
    // ---------------------------------------------------------------

    public function testGetFormat(): void
    {
        $this->assertSame('latex', $this->renderer->getFormat());
    }
}
