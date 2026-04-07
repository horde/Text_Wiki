<?php

declare(strict_types=1);

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use Horde\Text\Wiki\BBCodeParserSuperscript;
use Horde\Text\Wiki\XhtmlRendererSuperscript;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Test BBCode superscript parsing ([sup]text[/sup])
 */
#[CoversClass(BBCodeParserSuperscript::class)]
#[CoversClass(XhtmlRendererSuperscript::class)]
class BBCodeParseSuperscriptTest extends TestCase
{
    public function testSimpleSuperscript(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Superscript']);
        $source = 'E=mc[sup]2[/sup]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('E=mc<sup>2</sup>', $result);
    }

    public function testCaseInsensitiveUppercase(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Superscript']);
        $source = 'x[SUP]2[/SUP]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('x<sup>2</sup>', $result);
    }

    public function testMultipleSuperscripts(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Superscript']);
        $source = 'x[sup]2[/sup] + y[sup]2[/sup]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('x<sup>2</sup>', $result);
        $this->assertStringContainsString('y<sup>2</sup>', $result);
        $this->assertEquals(2, substr_count($result, '<sup>'));
    }

    public function testSuperscriptSpansNewlines(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Superscript']);
        $source = "[sup]text\nnewline[/sup]";
        $result = $wiki->transform($source, 'Xhtml');

        // BBCode superscript with 's' modifier should span newlines
        $this->assertStringContainsString('<sup>', $result);
        $this->assertStringContainsString('text', $result);
        $this->assertStringContainsString('newline', $result);
        $this->assertStringContainsString('</sup>', $result);
    }

    public function testUnclosedTag(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Superscript']);
        $source = '[sup]unclosed';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringNotContainsString('<sup>', $result);
        $this->assertStringContainsString('[sup]unclosed', $result);
    }

    public function testEmptySuperscriptTag(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Superscript']);
        $source = '[sup][/sup]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringNotContainsString('[sup]', $result);
    }

    public function testSuperscriptRenderToPlain(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Superscript']);
        $source = 'E=mc[sup]2[/sup]';
        $result = $wiki->transform($source, 'Plain');

        $this->assertStringContainsString('E=mc2', $result);
        $this->assertStringNotContainsString('<sup>', $result);
        $this->assertStringNotContainsString('[sup]', $result);
    }

    public function testSuperscriptWithFormatting(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Superscript', 'Bold']);
        $source = 'x[sup][b]2[/b][/sup]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('x<sup><b>2</b></sup>', $result);
    }
}
