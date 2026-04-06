<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test Default parser Colortext parsing
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class DefaultParseColortextTest extends TestCase
{
    public function testColortextParsing(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Colortext']);

        $input = "##red:colored text##";
        $wiki->parse($input);

        $colorTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Colortext');
        $this->assertGreaterThan(0, count($colorTokens));
    }

    public function testColortextToXhtmlRendering(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Colortext']);

        $input = "##blue:blue text##";
        $output = $wiki->transform($input, 'Xhtml');

        $this->assertMatchesRegularExpression('/(color:\s*blue|style="[^"]*color)/i', $output);
        $this->assertStringContainsString('blue text', $output);
    }

    public function testMultipleColors(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Colortext']);

        $input = "##red:red## and ##green:green## text";
        $wiki->parse($input);

        $colorTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Colortext');
        $this->assertGreaterThanOrEqual(2, count($colorTokens));
    }

    public function testHexColorCode(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Colortext']);

        $input = "##FF0000:red text##";
        $wiki->parse($input);

        $colorTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Colortext');
        $this->assertGreaterThanOrEqual(1, count($colorTokens));
    }
}
