<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test Tiki parser Italic formatting
 *
 * Tiki uses ''text'' for italic
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class TikiParseItalicTest extends TestCase
{
    public function testTikiItalicParsing(): void
    {
        $wiki = TextWikiBase::factory('Tiki', ['Italic']);

        $input = "This is ''italic text'' here.";
        $wiki->parse($input);

        $italicTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Italic');
        $this->assertGreaterThan(0, count($italicTokens));
    }

    public function testTikiItalicToXhtmlRendering(): void
    {
        $wiki = TextWikiBase::factory('Tiki', ['Italic']);

        $input = "Text with ''italic'' here.";
        $output = $wiki->transform($input, 'Xhtml');

        $this->assertMatchesRegularExpression('/<(em|i)>italic<\/(em|i)>/', $output);
    }

    public function testMultipleItalic(): void
    {
        $wiki = TextWikiBase::factory('Tiki', ['Italic']);

        $input = "''First'' and ''second'' italic.";
        $wiki->parse($input);

        $italicTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Italic');
        $this->assertGreaterThanOrEqual(2, count($italicTokens));
    }
}
