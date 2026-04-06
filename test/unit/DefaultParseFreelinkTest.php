<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test Default parser Freelink parsing
 *
 * Tests the ((double parens)) freelink syntax
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class DefaultParseFreelinkTest extends TestCase
{
    public function testSimpleFreelinkParsing(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Freelink']);

        $input = 'This is a ((Free Link)) in text.';
        $wiki->parse($input);

        $this->assertCount(1, $wiki->tokens);
        $this->assertEquals('Freelink', $wiki->tokens[0][0]);
        $this->assertEquals('Free Link', $wiki->tokens[0][1]['page']);
    }

    public function testFreelinkWithDisplayText(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Freelink']);

        $input = 'Check ((MyHomePage|My Home Page)) for details.';
        $wiki->parse($input);

        $this->assertCount(1, $wiki->tokens);
        $this->assertEquals('Freelink', $wiki->tokens[0][0]);
        $this->assertEquals('MyHomePage', $wiki->tokens[0][1]['page']);
        $this->assertEquals('My Home Page', $wiki->tokens[0][1]['text']);
    }

    public function testFreelinkWithAnchor(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Freelink']);

        $input = 'Jump to ((HomePage#Section1)) directly.';
        $wiki->parse($input);

        $this->assertCount(1, $wiki->tokens);
        $this->assertEquals('Freelink', $wiki->tokens[0][0]);
        $this->assertEquals('HomePage', $wiki->tokens[0][1]['page']);
        $this->assertEquals('#Section1', $wiki->tokens[0][1]['anchor']);
    }

    public function testFreelinkWithTextAndAnchor(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Freelink']);

        $input = 'See ((MyHomePage|My Home Page#Section1)) here.';
        $wiki->parse($input);

        $this->assertCount(1, $wiki->tokens);
        $this->assertEquals('Freelink', $wiki->tokens[0][0]);
        $this->assertEquals('MyHomePage', $wiki->tokens[0][1]['page']);
        $this->assertEquals('My Home Page', $wiki->tokens[0][1]['text']);
        $this->assertEquals('#Section1', $wiki->tokens[0][1]['anchor']);
    }

    public function testMediaWikiBracketsDoNotParse(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Freelink']);

        $input = 'This [[MediaWiki Style]] should not parse.';
        $wiki->parse($input);

        // Should have no tokens - MediaWiki syntax not supported
        $this->assertCount(0, $wiki->tokens);

        // Source should contain literal brackets
        $this->assertStringContainsString('[[MediaWiki Style]]', $wiki->source);
    }

    public function testMultipleFreelinksParsing(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Freelink']);

        $input = 'Visit ((Page One)) and ((Page Two)) for more.';
        $wiki->parse($input);

        $this->assertCount(2, $wiki->tokens);
        $this->assertEquals('Page One', $wiki->tokens[0][1]['page']);
        $this->assertEquals('Page Two', $wiki->tokens[1][1]['page']);
    }

    public function testFreelinkToXhtmlRendering(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Freelink']);

        $input = 'Visit ((Free Link)) here.';
        $output = $wiki->transform($input, 'Xhtml');

        $this->assertStringContainsString('href=', $output);
        $this->assertStringContainsString('Free Link', $output);
    }
}
