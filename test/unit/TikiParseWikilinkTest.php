<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test Tiki parser Wikilink syntax
 *
 * Tiki uses ((PageName)) for free links
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class TikiParseWikilinkTest extends TestCase
{
    public function testTikiSimpleWikilink(): void
    {
        $wiki = TextWikiBase::factory('Tiki', ['Wikilink']);

        $input = "See ((PageName)) for details.";
        $wiki->parse($input);

        $wikilinkTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Wikilink');
        $this->assertGreaterThan(0, count($wikilinkTokens));
    }

    public function testTikiWikilinkWithDescription(): void
    {
        $wiki = TextWikiBase::factory('Tiki', ['Wikilink']);

        $input = "See ((PageName|custom text)) here.";
        $wiki->parse($input);

        $wikilinkTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Wikilink');
        $this->assertGreaterThan(0, count($wikilinkTokens));
    }

    public function testTikiWikilinkToXhtml(): void
    {
        $wiki = TextWikiBase::factory('Tiki', ['Wikilink']);

        $input = "Link to ((HomePage)).";
        $output = $wiki->transform($input, 'Xhtml');

        $this->assertStringContainsString('<a', $output);
        $this->assertStringContainsString('HomePage', $output);
    }

    public function testMultipleWikilinks(): void
    {
        $wiki = TextWikiBase::factory('Tiki', ['Wikilink']);

        $input = "See ((Page1)) and ((Page2)).";
        $wiki->parse($input);

        $wikilinkTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Wikilink');
        $this->assertCount(4, $wikilinkTokens);
    }
}
