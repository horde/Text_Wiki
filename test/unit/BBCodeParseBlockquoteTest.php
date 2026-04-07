<?php

declare(strict_types=1);

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use Horde\Text\Wiki\BBCodeParserBlockquote;
use Horde\Text\Wiki\XhtmlRendererBlockquote;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Test BBCode blockquote parsing ([quote]text[/quote] and [quote=author]text[/quote])
 */
#[CoversClass(BBCodeParserBlockquote::class)]
#[CoversClass(XhtmlRendererBlockquote::class)]
class BBCodeParseBlockquoteTest extends TestCase
{
    public function testSimpleQuote(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Blockquote']);
        $source = '[quote]This is a quote.[/quote]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('This is a quote', $result);
        $this->assertStringContainsString('<blockquote>', $result);
        $this->assertStringContainsString('</blockquote>', $result);
    }

    public function testAttributedQuote(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Blockquote']);
        $source = '[quote=John Doe]This is John\'s quote.[/quote]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('This is John', $result);
        $this->assertStringContainsString('John Doe', $result);
        $this->assertStringContainsString('<blockquote>', $result);
    }

    public function testCaseInsensitiveUppercase(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Blockquote']);
        $source = '[QUOTE]quoted text[/QUOTE]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('quoted text', $result);
        $this->assertStringContainsString('<blockquote>', $result);
    }

    public function testQuoteWithFormatting(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Blockquote', 'Bold', 'Italic']);
        $source = '[quote]This is [b]bold[/b] and [i]italic[/i].[/quote]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<blockquote>', $result);
        $this->assertStringContainsString('<b>bold</b>', $result);
        $this->assertStringContainsString('<i>italic</i>', $result);
    }

    public function testNestedQuotes(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Blockquote']);
        $source = '[quote=Alice]Alice said [quote=Bob]Bob said this[/quote] Back to Alice[/quote]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('Alice', $result);
        $this->assertStringContainsString('Bob', $result);
        $this->assertStringContainsString('Alice said', $result);
        $this->assertStringContainsString('Bob said this', $result);

        // Should have nested blockquotes
        $blockquoteCount = substr_count($result, '<blockquote>');
        $this->assertGreaterThan(1, $blockquoteCount, 'Should have nested blockquotes');
    }

    public function testQuoteWithNewlines(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Blockquote']);
        $source = "[quote]\nLine 1\nLine 2\nLine 3\n[/quote]";
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('Line 1', $result);
        $this->assertStringContainsString('Line 2', $result);
        $this->assertStringContainsString('Line 3', $result);
        $this->assertStringContainsString('<blockquote>', $result);
    }

    public function testUnclosedQuoteTag(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Blockquote']);
        $source = '[quote]unclosed quote';
        $result = $wiki->transform($source, 'Xhtml');

        // Should not be parsed
        $this->assertStringNotContainsString('<blockquote>', $result);
        $this->assertStringContainsString('[quote]unclosed quote', $result);
    }

    public function testEmptyQuote(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Blockquote']);
        $source = '[quote][/quote]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringNotContainsString('[quote]', $result);
    }

    public function testQuoteRenderToPlain(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Blockquote']);
        $source = '[quote=John]John said this[/quote]';
        $result = $wiki->transform($source, 'Plain');

        $this->assertStringContainsString('John said this', $result);
        $this->assertStringNotContainsString('<blockquote>', $result);
        $this->assertStringNotContainsString('[quote]', $result);
    }

    public function testMultipleQuotes(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Blockquote']);
        $source = '[quote]First quote[/quote] Some text [quote]Second quote[/quote]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('First quote', $result);
        $this->assertStringContainsString('Second quote', $result);
        $this->assertEquals(2, substr_count($result, '<blockquote>'));
    }
}
