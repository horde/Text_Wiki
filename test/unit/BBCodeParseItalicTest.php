<?php

declare(strict_types=1);

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use Horde\Text\Wiki\BBCodeParserItalic;
use Horde\Text\Wiki\XhtmlRendererItalic;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Test BBCode italic parsing ([i]text[/i])
 */
#[CoversClass(BBCodeParserItalic::class)]
#[CoversClass(XhtmlRendererItalic::class)]
class BBCodeParseItalicTest extends TestCase
{
    public function testSimpleItalic(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Italic']);
        $source = '[i]italic text[/i]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<i>italic text</i>', $result);
    }

    public function testCaseInsensitiveUppercase(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Italic']);
        $source = '[I]italic text[/I]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<i>italic text</i>', $result);
    }

    public function testCaseInsensitiveMixed(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Italic']);
        $source = '[i]text one[/i] and [I]text two[/I]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<i>text one</i>', $result);
        $this->assertStringContainsString('<i>text two</i>', $result);
    }

    public function testNestedItalicWithBold(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Bold', 'Italic']);
        $source = '[i][b]italic bold[/b][/i]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<i><b>italic bold</b></i>', $result);
    }

    public function testMultipleItalicSections(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Italic']);
        $source = 'Some [i]italic[/i] and more [i]italic[/i] text';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<i>italic</i>', $result);
        $this->assertEquals(2, substr_count($result, '<i>'));
        $this->assertEquals(2, substr_count($result, '</i>'));
    }

    public function testItalicSpansNewlines(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Italic']);
        $source = "[i]italic text\nwith newline[/i]";
        $result = $wiki->transform($source, 'Xhtml');

        // BBCode italic with 's' modifier should span newlines
        $this->assertStringContainsString('<i>', $result);
        $this->assertStringContainsString('italic text', $result);
        $this->assertStringContainsString('with newline', $result);
        $this->assertStringContainsString('</i>', $result);
    }

    public function testUnclosedTag(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Italic']);
        $source = '[i]unclosed italic';
        $result = $wiki->transform($source, 'Xhtml');

        // Should not be parsed as italic
        $this->assertStringNotContainsString('<i>', $result);
        $this->assertStringContainsString('[i]unclosed italic', $result);
    }

    public function testEmptyItalicTag(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Italic']);
        $source = '[i][/i]';
        $result = $wiki->transform($source, 'Xhtml');

        // Should produce empty italic tags or no tags
        $this->assertStringNotContainsString('[i]', $result);
    }

    public function testItalicRenderToPlain(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Italic']);
        $source = 'Some [i]italic[/i] text';
        $result = $wiki->transform($source, 'Plain');

        // Plain should strip formatting
        $this->assertStringContainsString('italic', $result);
        $this->assertStringNotContainsString('<i>', $result);
        $this->assertStringNotContainsString('[i]', $result);
    }

    public function testCombinedBoldItalic(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Bold', 'Italic']);
        $source = '[b]bold[/b] and [i]italic[/i]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<b>bold</b>', $result);
        $this->assertStringContainsString('<i>italic</i>', $result);
    }
}
