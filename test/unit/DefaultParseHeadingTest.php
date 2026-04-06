<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test Default parser Heading parsing
 *
 * Tests the + heading syntax (not = like MediaWiki)
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class DefaultParseHeadingTest extends TestCase
{
    public function testHeadingLevel1(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Heading']);

        $input = "+ Heading Level 1\n";
        $wiki->parse($input);

        $this->assertCount(1, $wiki->tokens);
        $this->assertEquals('Heading', $wiki->tokens[0][0]);
        $this->assertEquals(1, $wiki->tokens[0][1]['level']);
        $this->assertEquals('Heading Level 1', $wiki->tokens[0][1]['text']);
    }

    public function testHeadingLevel2(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Heading']);

        $input = "++ Heading Level 2\n";
        $wiki->parse($input);

        $this->assertCount(1, $wiki->tokens);
        $this->assertEquals('Heading', $wiki->tokens[0][0]);
        $this->assertEquals(2, $wiki->tokens[0][1]['level']);
        $this->assertEquals('Heading Level 2', $wiki->tokens[0][1]['text']);
    }

    public function testHeadingLevel3To6(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Heading']);

        $input = "+++ Level 3\n";
        $input .= "++++ Level 4\n";
        $input .= "+++++ Level 5\n";
        $input .= "++++++ Level 6\n";

        $wiki->parse($input);

        $this->assertCount(4, $wiki->tokens);
        $this->assertEquals(3, $wiki->tokens[0][1]['level']);
        $this->assertEquals(4, $wiki->tokens[1][1]['level']);
        $this->assertEquals(5, $wiki->tokens[2][1]['level']);
        $this->assertEquals(6, $wiki->tokens[3][1]['level']);
    }

    public function testMultipleHeadings(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Heading']);

        $input = "+ First Heading\n";
        $input .= "Some text here.\n";
        $input .= "++ Second Heading\n";
        $input .= "More text.\n";
        $input .= "+++ Third Heading\n";

        $wiki->parse($input);

        $this->assertCount(3, $wiki->tokens);
        $this->assertEquals('First Heading', $wiki->tokens[0][1]['text']);
        $this->assertEquals('Second Heading', $wiki->tokens[1][1]['text']);
        $this->assertEquals('Third Heading', $wiki->tokens[2][1]['text']);
    }

    public function testHeadingToXhtmlRendering(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Heading']);

        $input = "+ Main Heading\n";
        $output = $wiki->transform($input, 'Xhtml');

        $this->assertStringContainsString('<h1', $output);
        $this->assertStringContainsString('Main Heading', $output);
        $this->assertStringContainsString('</h1>', $output);
    }

    public function testHeadingLevel2ToXhtml(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Heading']);

        $input = "++ Sub Heading\n";
        $output = $wiki->transform($input, 'Xhtml');

        $this->assertStringContainsString('<h2', $output);
        $this->assertStringContainsString('Sub Heading', $output);
        $this->assertStringContainsString('</h2>', $output);
    }

    public function testHeadingWithSpecialCharacters(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Heading']);

        $input = "+ Heading with \"quotes\" and 'apostrophes'\n";
        $wiki->parse($input);

        $this->assertCount(1, $wiki->tokens);
        $this->assertStringContainsString('quotes', $wiki->tokens[0][1]['text']);
        $this->assertStringContainsString('apostrophes', $wiki->tokens[0][1]['text']);
    }

    public function testEqualsSignDoesNotCreateHeading(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Heading']);

        $input = "= Not a heading in Default =\n";
        $wiki->parse($input);

        // Should NOT create heading tokens with = syntax
        $headingTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Heading');
        $this->assertCount(0, $headingTokens);
    }
}
