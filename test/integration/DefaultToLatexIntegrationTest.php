<?php

namespace Horde\Text\Wiki\Test\Integration;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Integration test: Default engine to LaTeX output
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class DefaultToLatexIntegrationTest extends TestCase
{
    private TextWikiBase $wiki;

    protected function setUp(): void
    {
        $this->wiki = TextWikiBase::factory('Default');
    }

    public function testSimpleDocumentToLatex(): void
    {
        $input = <<<WIKI
+ Main Heading

This is a paragraph with '''bold''' text.

* List item 1
* List item 2
WIKI;

        $output = $this->wiki->transform($input, 'Latex');

        $this->assertIsString($output);
        $this->assertStringContainsString('\\documentclass', $output);
        $this->assertStringContainsString('\\begin{document}', $output);
        $this->assertStringContainsString('\\end{document}', $output);
        $this->assertStringContainsString('Main Heading', $output);
    }

    public function testLatexFormattingCommands(): void
    {
        $input = "'''bold''' and ''italic''";
        $output = $this->wiki->transform($input, 'Latex');

        // LaTeX uses \textbf{} and \textit{} or similar
        $this->assertIsString($output);
        $this->assertStringContainsString('bold', $output);
        $this->assertStringContainsString('italic', $output);
    }

    public function testLatexListOutput(): void
    {
        $input = <<<WIKI
* Item 1
* Item 2

# Numbered 1
# Numbered 2
WIKI;

        $output = $this->wiki->transform($input, 'Latex');

        // LaTeX uses \begin{itemize} or \begin{enumerate}
        $this->assertMatchesRegularExpression('/\\\\begin\{(itemize|enumerate)\}/', $output);
        $this->assertMatchesRegularExpression('/\\\\end\{(itemize|enumerate)\}/', $output);
    }

    public function testLatexDocumentStructure(): void
    {
        $input = <<<WIKI
+ Chapter
++ Section
+++ Subsection
WIKI;

        $output = $this->wiki->transform($input, 'Latex');

        // LaTeX document has proper structure
        $this->assertStringContainsString('\\documentclass', $output);
        $this->assertStringContainsString('Chapter', $output);
        $this->assertStringContainsString('Section', $output);
    }
}
