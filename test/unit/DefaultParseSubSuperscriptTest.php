<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test Default parser Subscript and Superscript parsing
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class DefaultParseSubSuperscriptTest extends TestCase
{
    public function testSubscriptParsing(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Subscript']);

        $input = "H,,2,,O is water.";
        $wiki->parse($input);

        $subTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Subscript');
        $this->assertGreaterThan(0, count($subTokens));
    }

    public function testSuperscriptParsing(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Superscript']);

        $input = "E=mc^^2^^ formula.";
        $wiki->parse($input);

        $supTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Superscript');
        $this->assertGreaterThan(0, count($supTokens));
    }

    public function testSubscriptToXhtmlRendering(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Subscript']);

        $input = "H,,2,,O";
        $output = $wiki->transform($input, 'Xhtml');

        $this->assertStringContainsString('<sub>', $output);
        $this->assertStringContainsString('2', $output);
        $this->assertStringContainsString('</sub>', $output);
    }

    public function testSuperscriptToXhtmlRendering(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Superscript']);

        $input = "x^^2^^";
        $output = $wiki->transform($input, 'Xhtml');

        $this->assertStringContainsString('<sup>', $output);
        $this->assertStringContainsString('2', $output);
        $this->assertStringContainsString('</sup>', $output);
    }

    public function testMultipleSubscripts(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Subscript']);

        $input = "H,,2,,O and CO,,2,,";
        $wiki->parse($input);

        $subTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Subscript');
        $this->assertCount(4, $subTokens);
    }

    public function testMultipleSuperscripts(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Superscript']);

        $input = "x^^2^^ + y^^3^^";
        $wiki->parse($input);

        $supTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Superscript');
        $this->assertCount(4, $supTokens);
    }
}
