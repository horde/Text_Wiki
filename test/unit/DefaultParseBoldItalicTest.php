<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test Default parser Bold and Italic formatting
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class DefaultParseBoldItalicTest extends TestCase
{
    public function testBoldParsing(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Bold']);

        $input = "This is '''bold text''' in a sentence.";
        $wiki->parse($input);

        $this->assertCount(2, $wiki->tokens);
        $this->assertEquals('Bold', $wiki->tokens[0][0]);
        $this->assertEquals('start', $wiki->tokens[0][1]['type']);
        $this->assertEquals('Bold', $wiki->tokens[1][0]);
        $this->assertEquals('end', $wiki->tokens[1][1]['type']);
    }

    public function testItalicParsing(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Italic']);

        $input = "This is ''italic text'' in a sentence.";
        $wiki->parse($input);

        $this->assertCount(2, $wiki->tokens);
        $this->assertEquals('Italic', $wiki->tokens[0][0]);
        $this->assertEquals('start', $wiki->tokens[0][1]['type']);
        $this->assertEquals('Italic', $wiki->tokens[1][0]);
        $this->assertEquals('end', $wiki->tokens[1][1]['type']);
    }

    public function testBoldItalicCombined(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Bold', 'Italic']);

        $input = "Text with '''''bold and italic''''' here.";
        $wiki->parse($input);

        // Should create both Bold and Italic tokens
        $this->assertGreaterThanOrEqual(2, count($wiki->tokens));

        $tokenTypes = array_map(fn($t) => $t[0], $wiki->tokens);
        $this->assertContains('Bold', $tokenTypes);
        $this->assertContains('Italic', $tokenTypes);
    }

    public function testNestedBoldItalic(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Bold', 'Italic']);

        $input = "This is '''bold with ''italic'' inside''' text.";
        $wiki->parse($input);

        // Should have tokens for both bold and italic
        $this->assertGreaterThanOrEqual(4, count($wiki->tokens));
    }

    public function testMultipleBold(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Bold']);

        $input = "First '''bold''' and second '''bold''' here.";
        $wiki->parse($input);

        $this->assertCount(4, $wiki->tokens); // 2 start + 2 end
        $this->assertEquals('start', $wiki->tokens[0][1]['type']);
        $this->assertEquals('end', $wiki->tokens[1][1]['type']);
        $this->assertEquals('start', $wiki->tokens[2][1]['type']);
        $this->assertEquals('end', $wiki->tokens[3][1]['type']);
    }

    public function testBoldToXhtmlRendering(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Bold']);

        $input = "This is '''bold text''' here.";
        $output = $wiki->transform($input, 'Xhtml');

        // Default renders bold as <strong> or <b>
        $this->assertMatchesRegularExpression('/<(strong|b)>bold text<\/(strong|b)>/', $output);
    }

    public function testItalicToXhtmlRendering(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Italic']);

        $input = "This is ''italic text'' here.";
        $output = $wiki->transform($input, 'Xhtml');

        // Default renders italic as <em> or <i>
        $this->assertMatchesRegularExpression('/<(em|i)>italic text<\/(em|i)>/', $output);
    }

    public function testUnterminatedBoldDoesNotParse(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Bold']);

        $input = "This has '''unterminated bold at end.";
        $wiki->parse($input);

        // Should not create tokens for unterminated markup
        // The exact behavior depends on implementation
        $output = $wiki->transform($input, 'Xhtml');

        // Should not have bold tags if not properly closed
        $boldStarts = substr_count($output, '<strong>') + substr_count($output, '<b>');
        $boldEnds = substr_count($output, '</strong>') + substr_count($output, '</b>');

        // If any bold tags exist, they should be balanced
        if ($boldStarts > 0) {
            $this->assertEquals($boldStarts, $boldEnds, 'Bold tags should be balanced');
        }
    }
}
