<?php

declare(strict_types=1);

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use Horde\Text\Wiki\BBCodeParserUnderline;
use Horde\Text\Wiki\XhtmlRendererUnderline;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Test BBCode underline parsing ([u]text[/u])
 */
#[CoversClass(BBCodeParserUnderline::class)]
#[CoversClass(XhtmlRendererUnderline::class)]
class BBCodeParseUnderlineTest extends TestCase
{
    public function testSimpleUnderline(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Underline']);
        $source = '[u]underlined text[/u]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<u>underlined text</u>', $result);
    }

    public function testCaseInsensitiveUppercase(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Underline']);
        $source = '[U]underlined text[/U]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<u>underlined text</u>', $result);
    }

    public function testNestedUnderlineWithBold(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Bold', 'Underline']);
        $source = '[u][b]underlined bold[/b][/u]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<u><b>underlined bold</b></u>', $result);
    }

    public function testMultipleUnderlineSections(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Underline']);
        $source = 'Some [u]underlined[/u] and more [u]underlined[/u] text';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<u>underlined</u>', $result);
        $this->assertEquals(2, substr_count($result, '<u>'));
        $this->assertEquals(2, substr_count($result, '</u>'));
    }

    public function testUnderlineSpansNewlines(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Underline']);
        $source = "[u]underlined text\nwith newline[/u]";
        $result = $wiki->transform($source, 'Xhtml');

        // BBCode underline with 's' modifier should span newlines
        $this->assertStringContainsString('<u>', $result);
        $this->assertStringContainsString('underlined text', $result);
        $this->assertStringContainsString('with newline', $result);
        $this->assertStringContainsString('</u>', $result);
    }

    public function testUnclosedTag(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Underline']);
        $source = '[u]unclosed underline';
        $result = $wiki->transform($source, 'Xhtml');

        // Should not be parsed as underline
        $this->assertStringNotContainsString('<u>', $result);
        $this->assertStringContainsString('[u]unclosed underline', $result);
    }

    public function testEmptyUnderlineTag(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Underline']);
        $source = '[u][/u]';
        $result = $wiki->transform($source, 'Xhtml');

        // Should produce empty underline tags or no tags
        $this->assertStringNotContainsString('[u]', $result);
    }

    public function testUnderlineRenderToPlain(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Underline']);
        $source = 'Some [u]underlined[/u] text';
        $result = $wiki->transform($source, 'Plain');

        // Plain should strip formatting
        $this->assertStringContainsString('underlined', $result);
        $this->assertStringNotContainsString('<u>', $result);
        $this->assertStringNotContainsString('[u]', $result);
    }

    public function testCombinedFormatting(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Bold', 'Italic', 'Underline']);
        $source = '[b][i][u]all three[/u][/i][/b]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<b><i><u>all three</u></i></b>', $result);
    }
}
