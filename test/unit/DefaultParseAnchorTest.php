<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test Default parser Anchor parsing
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class DefaultParseAnchorTest extends TestCase
{
    public function testAnchorParsing(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Anchor']);

        $input = 'Text with [# anchor-name] here.';
        $wiki->parse($input);

        $anchorTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Anchor');
        $this->assertCount(1, $anchorTokens);
    }

    public function testMultipleAnchors(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Anchor']);

        $input = '[# first] Some text [# second] More text [# third]';
        $wiki->parse($input);

        $anchorTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Anchor');
        $this->assertCount(3, $anchorTokens);
    }

    public function testAnchorToXhtmlRendering(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Anchor']);

        $input = '[# section1]';
        $output = $wiki->transform($input, 'Xhtml');

        $this->assertStringContainsString('id=', $output);
        $this->assertStringContainsString('section1', $output);
    }

    public function testAnchorWithHyphenAndUnderscore(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Anchor']);

        $input = '[# my-anchor_name] and [# another_one]';
        $wiki->parse($input);

        $anchorTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Anchor');
        $this->assertCount(2, $anchorTokens);
    }
}
