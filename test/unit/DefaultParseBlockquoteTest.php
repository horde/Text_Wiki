<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test Default parser Blockquote parsing
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class DefaultParseBlockquoteTest extends TestCase
{
    public function testBlockquoteParsing(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Blockquote']);
        $input = '
> test 1
> test 2
>> test 11
>> test 22
';

        $wiki->parse($input, 'Xhtml');

        // Check that source was tokenized correctly (contains protected byte markers)
        $this->assertStringContainsString('test 1', $wiki->source);
        $this->assertStringContainsString('test 22', $wiki->source);

        // Check tokens structure
        $this->assertCount(4, $wiki->tokens);

        // First blockquote start (level 1)
        $this->assertEquals('Blockquote', $wiki->tokens[0][0]);
        $this->assertEquals('start', $wiki->tokens[0][1]['type']);
        $this->assertEquals(1, $wiki->tokens[0][1]['level']);

        // Second blockquote start (level 2)
        $this->assertEquals('Blockquote', $wiki->tokens[1][0]);
        $this->assertEquals('start', $wiki->tokens[1][1]['type']);
        $this->assertEquals(2, $wiki->tokens[1][1]['level']);

        // Second blockquote end (level 2)
        $this->assertEquals('Blockquote', $wiki->tokens[2][0]);
        $this->assertEquals('end', $wiki->tokens[2][1]['type']);
        $this->assertEquals(2, $wiki->tokens[2][1]['level']);

        // First blockquote end (level 1)
        $this->assertEquals('Blockquote', $wiki->tokens[3][0]);
        $this->assertEquals('end', $wiki->tokens[3][1]['type']);
        $this->assertEquals(1, $wiki->tokens[3][1]['level']);
    }
}
