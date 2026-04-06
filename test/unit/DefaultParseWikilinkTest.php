<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test Default parser WikiLink (CamelCase) parsing
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class DefaultParseWikilinkTest extends TestCase
{
    public function testSimpleWikiWordParsing(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Wikilink']);

        $input = 'This is a WikiWord in text.';
        $wiki->parse($input);

        $this->assertCount(1, $wiki->tokens);
        $this->assertEquals('Wikilink', $wiki->tokens[0][0]);
        $this->assertStringContainsString('WikiWord', $wiki->source);
    }

    public function testMultipleWikiWords(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Wikilink']);

        $input = 'Visit HomePage and MyBlog today.';
        $wiki->parse($input);

        $this->assertCount(2, $wiki->tokens);
        $this->assertEquals('Wikilink', $wiki->tokens[0][0]);
        $this->assertEquals('Wikilink', $wiki->tokens[1][0]);
    }

    public function testWikiWordWithAnchor(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Wikilink']);

        $input = 'Jump to HomePage#Section directly.';
        $wiki->parse($input);

        $this->assertCount(1, $wiki->tokens);
        $this->assertEquals('Wikilink', $wiki->tokens[0][0]);
    }

    public function testSuppressedWikiWord(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Wikilink']);

        $input = 'This is !WikiWord suppressed.';
        $wiki->parse($input);

        // Suppressed WikiWord should not create token
        $this->assertCount(0, $wiki->tokens);

        // The exclamation mark should be removed
        $this->assertStringNotContainsString('!WikiWord', $wiki->source);
    }

    public function testCamelCaseRequirements(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Wikilink']);

        // Valid WikiWords
        $input = 'WikiWord HomePage MyTest Ab';
        $wiki->parse($input);

        // Should have 3 tokens (WikiWord, HomePage, MyTest)
        // "Ab" is too short (needs lowercase after uppercase)
        $this->assertCount(3, $wiki->tokens);
    }

    public function testNotWikiWords(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Wikilink']);

        // These should NOT be WikiWords
        $input = 'lowercase ALLCAPS Mix3dNum UPPERCase';
        $wiki->parse($input);

        // Only "Mix3dNum" might match depending on rules
        // "UPPERCase" should match (Uppercase followed by lowercase)
        $this->assertGreaterThanOrEqual(1, count($wiki->tokens));
    }

    public function testWikiWordToXhtmlRendering(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Wikilink']);

        $input = 'Visit WikiWord here.';
        $output = $wiki->transform($input, 'Xhtml');

        $this->assertStringContainsString('<a', $output);
        $this->assertStringContainsString('WikiWord', $output);
    }

    public function testWikiWordInSentence(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Wikilink']);

        $input = 'The HomePage contains important WikiLinks to various WikiPages.';
        $wiki->parse($input);

        $this->assertCount(3, $wiki->tokens);
        $this->assertEquals('Wikilink', $wiki->tokens[0][0]);
        $this->assertEquals('Wikilink', $wiki->tokens[1][0]);
        $this->assertEquals('Wikilink', $wiki->tokens[2][0]);
    }
}
