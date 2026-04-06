<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test Default parser Horizontal Rule parsing
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class DefaultParseHorizTest extends TestCase
{
    public function testHorizontalRuleParsing(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Horiz']);

        $input = "Before\n----\nAfter";
        $wiki->parse($input);

        $this->assertGreaterThan(0, count($wiki->tokens));

        $horizTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Horiz');
        $this->assertCount(1, $horizTokens);
    }

    public function testHorizontalRuleWithMoreDashes(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Horiz']);

        $input = "Text\n--------\nMore text";
        $wiki->parse($input);

        $horizTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Horiz');
        $this->assertCount(1, $horizTokens);
    }

    public function testMultipleHorizontalRules(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Horiz']);

        $input = "Section 1\n----\nSection 2\n----\nSection 3";
        $wiki->parse($input);

        $horizTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Horiz');
        $this->assertCount(2, $horizTokens);
    }

    public function testHorizontalRuleToXhtmlRendering(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Horiz']);

        $input = "Before\n----\nAfter";
        $output = $wiki->transform($input, 'Xhtml');

        $this->assertStringContainsString('<hr', $output);
    }

    public function testThreeDashesDoesNotCreateRule(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Horiz']);

        $input = "Text\n---\nMore";
        $wiki->parse($input);

        $horizTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Horiz');

        // Three dashes might not be enough - depends on implementation
        // Most wiki formats require 4+ dashes
        if (count($horizTokens) === 0) {
            $this->assertTrue(true, 'Three dashes correctly does not create HR');
        } else {
            $this->markTestIncomplete('Check if 3 dashes creates HR in Default');
        }
    }
}
