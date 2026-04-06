<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test Default parser Underline parsing
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class DefaultParseUnderlineTest extends TestCase
{
    public function testUnderlineParsing(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Underline']);

        $input = "This is __underlined__ text.";
        $wiki->parse($input);

        $underlineTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Underline');
        $this->assertGreaterThan(0, count($underlineTokens));
    }

    public function testUnderlineToXhtmlRendering(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Underline']);

        $input = "__underlined__";
        $output = $wiki->transform($input, 'Xhtml');

        $this->assertMatchesRegularExpression('/<u>|text-decoration:\s*underline/i', $output);
        $this->assertStringContainsString('underlined', $output);
    }

    public function testMultipleUnderlines(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Underline']);

        $input = "First __underline__ and second __underline__.";
        $wiki->parse($input);

        $underlineTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Underline');
        $this->assertCount(4, $underlineTokens);
    }

    public function testUnderlineWithBold(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Underline', 'Bold']);

        $input = "This is __underlined__ and '''bold'''.";
        $wiki->parse($input);

        $this->assertGreaterThan(0, count($wiki->tokens));
    }
}
