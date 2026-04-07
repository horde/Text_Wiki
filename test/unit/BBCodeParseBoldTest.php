<?php

declare(strict_types=1);

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use Horde\Text\Wiki\BBCodeParserBold;
use Horde\Text\Wiki\XhtmlRendererBold;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Test BBCode bold parsing ([b]text[/b])
 */
#[CoversClass(BBCodeParserBold::class)]
#[CoversClass(XhtmlRendererBold::class)]
class BBCodeParseBoldTest extends TestCase
{
    public function testSimpleBold(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Bold']);
        $source = '[b]bold text[/b]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<b>bold text</b>', $result);
    }

    public function testCaseInsensitiveUppercase(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Bold']);
        $source = '[B]bold text[/B]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<b>bold text</b>', $result);
    }

    public function testCaseInsensitiveMixed(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Bold']);
        $source = '[b]text one[/b] and [B]text two[/B]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<b>text one</b>', $result);
        $this->assertStringContainsString('<b>text two</b>', $result);
    }

    public function testNestedBoldWithItalic(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Bold', 'Italic']);
        $source = '[b][i]bold italic[/i][/b]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<b><i>bold italic</i></b>', $result);
    }

    public function testMultipleBoldSections(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Bold']);
        $source = 'Some [b]bold[/b] and more [b]bold[/b] text';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<b>bold</b>', $result);
        $this->assertEquals(2, substr_count($result, '<b>'));
        $this->assertEquals(2, substr_count($result, '</b>'));
    }

    public function testBoldSpansNewlines(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Bold']);
        $source = "[b]bold text\nwith newline[/b]";
        $result = $wiki->transform($source, 'Xhtml');

        // BBCode bold with 's' modifier should span newlines
        $this->assertStringContainsString('<b>', $result);
        $this->assertStringContainsString('bold text', $result);
        $this->assertStringContainsString('with newline', $result);
        $this->assertStringContainsString('</b>', $result);
    }

    public function testUnclosedTag(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Bold']);
        $source = '[b]unclosed bold';
        $result = $wiki->transform($source, 'Xhtml');

        // Should not be parsed as bold
        $this->assertStringNotContainsString('<b>', $result);
        $this->assertStringContainsString('[b]unclosed bold', $result);
    }

    public function testEmptyBoldTag(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Bold']);
        $source = '[b][/b]';
        $result = $wiki->transform($source, 'Xhtml');

        // Should produce empty bold tags or no tags
        // Depending on implementation
        $this->assertStringNotContainsString('[b]', $result);
    }

    public function testBoldRenderToPlain(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Bold']);
        $source = 'Some [b]bold[/b] text';
        $result = $wiki->transform($source, 'Plain');

        // Plain should strip formatting
        $this->assertStringContainsString('bold', $result);
        $this->assertStringNotContainsString('<b>', $result);
        $this->assertStringNotContainsString('[b]', $result);
    }

    public function testBoldWithSpecialCharacters(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Bold']);
        $source = '[b]text with <special> & "chars"[/b]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<b>', $result);
        $this->assertStringContainsString('</b>', $result);
        // Special chars should be present (may be escaped by renderer)
        $this->assertStringContainsString('special', $result);
    }
}
