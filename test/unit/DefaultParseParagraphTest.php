<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test Default parser Paragraph parsing
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class DefaultParseParagraphTest extends TestCase
{
    public function testSingleParagraph(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Paragraph', 'Newline']);

        $input = "This is a single paragraph.";
        $wiki->parse($input);

        $paragraphTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Paragraph');
        $this->assertGreaterThanOrEqual(1, count($paragraphTokens));
    }

    public function testMultipleParagraphs(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Paragraph', 'Newline']);

        $input = "First paragraph.\n\nSecond paragraph.";
        $wiki->parse($input);

        $paragraphTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Paragraph');
        $this->assertGreaterThanOrEqual(2, count($paragraphTokens));
    }

    public function testParagraphToXhtmlRendering(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Paragraph', 'Newline']);

        $input = "This is a paragraph.\n\nThis is another paragraph.";
        $output = $wiki->transform($input, 'Xhtml');

        $this->assertStringContainsString('<p>', $output);
        $this->assertStringContainsString('</p>', $output);
        $this->assertStringContainsString('This is a paragraph', $output);
        $this->assertStringContainsString('This is another paragraph', $output);
    }

    public function testParagraphWithSingleNewline(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Paragraph', 'Newline']);

        $input = "Line one\nLine two";
        $wiki->parse($input);

        // Single newline should be within same paragraph
        $paragraphTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Paragraph');
        $this->assertGreaterThanOrEqual(1, count($paragraphTokens));
    }

    public function testEmptyLinesCreateParagraphBreaks(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Paragraph', 'Newline']);

        $input = "Para 1\n\n\n\nPara 2";
        $wiki->parse($input);

        // Multiple empty lines should still create paragraph separation
        $paragraphTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Paragraph');
        $this->assertGreaterThanOrEqual(2, count($paragraphTokens));
    }
}
