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
use Horde\Text\Wiki\Renderer\Latex;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests: Yawiki parser → LaTeX renderer
 *
 * Verifies that Yawiki wikitext is correctly parsed into AST
 * and rendered to LaTeX for all supported token types.
 *
 * @coversNothing
 */
class YawikiToLatexIntegrationTest extends TestCase
{
    private YawikiParser $parser;
    private Latex $renderer;

    protected function setUp(): void
    {
        $this->parser = new YawikiParser();
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
        $this->assertStringContainsString('\\textit{italic text}', $result);
    }

    public function testStrong(): void
    {
        $result = $this->render('**strong text**');
        $this->assertStringContainsString('\\textbf{strong text}', $result);
    }

    public function testEmphasis(): void
    {
        $result = $this->render('//emphasis text//');
        $this->assertStringContainsString('\\textsl{emphasis text}', $result);
    }

    public function testUnderline(): void
    {
        $result = $this->render('__underlined__');
        $this->assertStringContainsString('\\underline{underlined}', $result);
    }

    public function testMonospace(): void
    {
        $result = $this->render('{{monospace}}');
        $this->assertStringContainsString('\\texttt{monospace}', $result);
    }

    public function testSuperscript(): void
    {
        $result = $this->render('E=mc^^2^^');
        $this->assertStringContainsString('\\textsuperscript{2}', $result);
    }

    public function testSubscript(): void
    {
        $result = $this->render('H,,2,,O');
        $this->assertStringContainsString('\\textsubscript{2}', $result);
    }

    public function testBreak(): void
    {
        $result = $this->render("Line one _\nLine two");
        $this->assertStringContainsString('\\newline', $result);
    }

    // ---------------------------------------------------------------
    // Headings
    // ---------------------------------------------------------------

    public function testHeading1(): void
    {
        $result = $this->render('+ Main Title');
        $this->assertStringContainsString('\\part{Main Title}', $result);
    }

    public function testHeading2(): void
    {
        $result = $this->render('++ Section');
        $this->assertStringContainsString('\\section{Section}', $result);
    }

    public function testHeading3(): void
    {
        $result = $this->render('+++ Subsection');
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

    public function testFreelink(): void
    {
        $result = $this->render('((Free Link))');
        $this->assertStringContainsString('Free Link', $result);
    }

    public function testPhplookup(): void
    {
        $result = $this->render('[[php strlen]]');
        $this->assertStringContainsString('strlen', $result);
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
        $result = $this->render('> quoted text');
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
        $input = "|| ~ Header 1 || ~ Header 2 ||\n|| Cell 1 || Cell 2 ||";
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
        $result = $this->render(': Term : Definition');
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
        $result = $this->render('[[toc]]');
        $this->assertStringContainsString('\\tableofcontents', $result);
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
        $this->assertStringContainsString('\\sout{deleted text}', $result);
    }

    public function testReviseIns(): void
    {
        $result = $this->render('@@+++inserted text@@');
        $this->assertStringContainsString('\\underline{inserted text}', $result);
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
++ Main Heading

Some paragraph with '''bold''' text.

* Item 1
* Item 2

----

> A famous quote
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
