<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test Tiki parser Heading syntax
 *
 * Tiki uses ! for headings (not + like Default, not = like MediaWiki)
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class TikiParseHeadingTest extends TestCase
{
    public function testTikiHeadingLevel1(): void
    {
        $wiki = TextWikiBase::factory('Tiki', ['Heading']);

        $input = "\n!Heading Level 1\n";
        $wiki->parse($input);

        $headingTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Heading');
        $this->assertCount(4, $headingTokens);

        $token = reset($headingTokens);
        $this->assertEquals(1, $token[1]['level']);
    }

    public function testTikiHeadingLevel2(): void
    {
        $wiki = TextWikiBase::factory('Tiki', ['Heading']);

        $input = "\n!!Heading Level 2\n";
        $wiki->parse($input);

        $headingTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Heading');
        $this->assertCount(4, $headingTokens);

        $token = reset($headingTokens);
        $this->assertEquals(2, $token[1]['level']);
    }

    public function testTikiHeadingLevel3(): void
    {
        $wiki = TextWikiBase::factory('Tiki', ['Heading']);

        $input = "\n!!!Heading Level 3\n";
        $wiki->parse($input);

        $headingTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Heading');
        $this->assertCount(4, $headingTokens);

        $token = reset($headingTokens);
        $this->assertEquals(3, $token[1]['level']);
    }

    public function testTikiHeadingToXhtml(): void
    {
        $wiki = TextWikiBase::factory('Tiki', ['Heading']);

        $input = "\n!Main Heading\n";
        $output = $wiki->transform($input, 'Xhtml');

        $this->assertStringContainsString('<h1', $output);
        $this->assertStringContainsString('Main Heading', $output);
        $this->assertStringContainsString('</h1>', $output);
    }

    public function testMultipleHeadings(): void
    {
        $wiki = TextWikiBase::factory('Tiki', ['Heading']);

        $input = "\n!First\n!!Second\n!!!Third\n";
        $wiki->parse($input);

        $headingTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Heading');
        $this->assertCount(12, $headingTokens);
    }
}
