<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Test\BBCode;

use Horde\Text\Wiki\BBCode\BBCodeTokenizer;
use Horde\Text\Wiki\TokenType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BBCodeTokenizer::class)]
class BBCodeTokenizerTest extends TestCase
{
    private BBCodeTokenizer $tokenizer;

    protected function setUp(): void
    {
        $this->tokenizer = new BBCodeTokenizer();
    }

    public function testSimpleTags(): void
    {
        $tokens = iterator_to_array($this->tokenizer->tokenize('[b]bold[/b]'));

        $this->assertCount(3, $tokens);
        $this->assertSame(TokenType::OPEN_TAG, $tokens[0]->type);
        $this->assertSame('bold', $tokens[0]->value);
        $this->assertSame(TokenType::TEXT, $tokens[1]->type);
        $this->assertSame('bold', $tokens[1]->value);
        $this->assertSame(TokenType::CLOSE_TAG, $tokens[2]->type);
        $this->assertSame('bold', $tokens[2]->value);
    }

    public function testTagWithAttribute(): void
    {
        $tokens = iterator_to_array($this->tokenizer->tokenize('[url=http://example.com]link[/url]'));

        $this->assertCount(3, $tokens);
        $this->assertSame(TokenType::OPEN_TAG, $tokens[0]->type);
        $this->assertSame('url', $tokens[0]->value);
        $this->assertSame(['href' => 'http://example.com'], $tokens[0]->attributes);
    }

    public function testQuotedAttribute(): void
    {
        $tokens = iterator_to_array($this->tokenizer->tokenize('[font="Times New Roman"]text[/font]'));

        $this->assertCount(3, $tokens);
        $this->assertSame('font', $tokens[0]->value);
        $this->assertSame(['font' => 'Times New Roman'], $tokens[0]->attributes);
    }

    public function testMultipleAttributes(): void
    {
        $tokens = iterator_to_array($this->tokenizer->tokenize('[tag foo=bar baz="qux"]text[/tag]'));

        $this->assertSame(['foo' => 'bar', 'baz' => 'qux'], $tokens[0]->attributes);
    }

    public function testInvalidTagTreatedAsText(): void
    {
        $tokens = iterator_to_array($this->tokenizer->tokenize('[unclosed tag'));

        // Invalid tags (missing ]) are split into TEXT tokens
        // '[unclosed tag' -> TEXT('[') + TEXT('unclosed tag')
        // This is correct: character-by-character parsing is more flexible
        // Structure builder merges consecutive TEXT tokens anyway
        $this->assertCount(2, $tokens);
        $this->assertSame(TokenType::TEXT, $tokens[0]->type);
        $this->assertSame('[', $tokens[0]->value);
        $this->assertSame(TokenType::TEXT, $tokens[1]->type);
        $this->assertSame('unclosed tag', $tokens[1]->value);
    }

    public function testNewlines(): void
    {
        $tokens = iterator_to_array($this->tokenizer->tokenize("line1\nline2"));

        $this->assertCount(3, $tokens);
        $this->assertSame(TokenType::TEXT, $tokens[0]->type);
        $this->assertSame('line1', $tokens[0]->value);
        $this->assertSame(TokenType::NEWLINE, $tokens[1]->type);
        $this->assertSame(TokenType::TEXT, $tokens[2]->type);
        $this->assertSame('line2', $tokens[2]->value);
    }

    public function testNestedTags(): void
    {
        $tokens = iterator_to_array($this->tokenizer->tokenize('[b][i]text[/i][/b]'));

        $this->assertCount(5, $tokens);
        $this->assertSame('bold', $tokens[0]->value);
        $this->assertSame('italic', $tokens[1]->value);
        $this->assertSame('text', $tokens[2]->value);
        $this->assertSame('italic', $tokens[3]->value);
        $this->assertSame('bold', $tokens[4]->value);
    }
}
