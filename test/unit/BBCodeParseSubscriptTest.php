<?php

declare(strict_types=1);

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use Horde\Text\Wiki\BBCodeParserSubscript;
use Horde\Text\Wiki\XhtmlRendererSubscript;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Test BBCode subscript parsing ([sub]text[/sub])
 */
#[CoversClass(BBCodeParserSubscript::class)]
#[CoversClass(XhtmlRendererSubscript::class)]
class BBCodeParseSubscriptTest extends TestCase
{
    public function testSimpleSubscript(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Subscript']);
        $source = 'H[sub]2[/sub]O';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('H<sub>2</sub>O', $result);
    }

    public function testCaseInsensitiveUppercase(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Subscript']);
        $source = 'H[SUB]2[/SUB]O';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('H<sub>2</sub>O', $result);
    }

    public function testMultipleSubscripts(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Subscript']);
        $source = 'C[sub]6[/sub]H[sub]12[/sub]O[sub]6[/sub]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('C<sub>6</sub>', $result);
        $this->assertStringContainsString('H<sub>12</sub>', $result);
        $this->assertStringContainsString('O<sub>6</sub>', $result);
        $this->assertEquals(3, substr_count($result, '<sub>'));
    }

    public function testSubscriptSpansNewlines(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Subscript']);
        $source = "[sub]text\nnewline[/sub]";
        $result = $wiki->transform($source, 'Xhtml');

        // BBCode subscript with 's' modifier should span newlines
        $this->assertStringContainsString('<sub>', $result);
        $this->assertStringContainsString('text', $result);
        $this->assertStringContainsString('newline', $result);
        $this->assertStringContainsString('</sub>', $result);
    }

    public function testUnclosedTag(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Subscript']);
        $source = '[sub]unclosed';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringNotContainsString('<sub>', $result);
        $this->assertStringContainsString('[sub]unclosed', $result);
    }

    public function testEmptySubscriptTag(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Subscript']);
        $source = '[sub][/sub]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringNotContainsString('[sub]', $result);
    }

    public function testSubscriptRenderToPlain(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Subscript']);
        $source = 'H[sub]2[/sub]O';
        $result = $wiki->transform($source, 'Plain');

        $this->assertStringContainsString('H2O', $result);
        $this->assertStringNotContainsString('<sub>', $result);
        $this->assertStringNotContainsString('[sub]', $result);
    }

    public function testSubscriptWithFormatting(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Subscript', 'Bold']);
        $source = 'H[sub][b]2[/b][/sub]O';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('H<sub><b>2</b></sub>O', $result);
    }

    public function testCombinedSuperscriptAndSubscript(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Superscript', 'Subscript']);
        $source = 'x[sub]i[/sub][sup]2[/sup]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('x<sub>i</sub><sup>2</sup>', $result);
    }
}
