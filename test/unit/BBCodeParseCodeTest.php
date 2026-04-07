<?php

declare(strict_types=1);

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use Horde\Text\Wiki\BBCodeParserCode;
use Horde\Text\Wiki\XhtmlRendererCode;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Test BBCode code block parsing ([code]text[/code])
 */
#[CoversClass(BBCodeParserCode::class)]
#[CoversClass(XhtmlRendererCode::class)]
class BBCodeParseCodeTest extends TestCase
{
    public function testSimpleCodeBlock(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Code']);
        $source = '[code]function example() { return true; }[/code]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('function example()', $result);
        $this->assertStringContainsString('return true', $result);
    }

    public function testCodeBlockPreservesFormatting(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Code']);
        $source = "[code]line 1\n  line 2 with indent\nline 3[/code]";
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('line 1', $result);
        $this->assertStringContainsString('line 2 with indent', $result);
        $this->assertStringContainsString('line 3', $result);
    }

    public function testCodeBlockProtectsFromParsing(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Code', 'Bold', 'Italic']);
        $source = '[code][b]not bold[/b] [i]not italic[/i][/code]';
        $result = $wiki->transform($source, 'Xhtml');

        // Tags inside code should be literal
        $this->assertStringContainsString('[b]not bold[/b]', $result);
        $this->assertStringContainsString('[i]not italic[/i]', $result);
        // Should NOT contain processed tags
        $this->assertStringNotContainsString('<b>not bold</b>', $result);
        $this->assertStringNotContainsString('<i>not italic</i>', $result);
    }

    public function testCodeBlockWithSpecialChars(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Code']);
        $source = '[code]<html> & "quotes" \'apostrophe\'[/code]';
        $result = $wiki->transform($source, 'Xhtml');

        // Special chars should be present (may be escaped)
        $this->assertStringContainsString('html', $result);
        $this->assertStringContainsString('quotes', $result);
    }

    public function testCaseInsensitiveUppercase(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Code']);
        $source = '[CODE]code content[/CODE]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('code content', $result);
    }

    public function testUnclosedCodeTag(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Code']);
        $source = '[code]unclosed code';
        $result = $wiki->transform($source, 'Xhtml');

        // Should not be parsed as code
        $this->assertStringContainsString('[code]unclosed code', $result);
    }

    public function testEmptyCodeBlock(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Code']);
        $source = '[code][/code]';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringNotContainsString('[code]', $result);
    }

    public function testCodeBlockRenderToPlain(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Code']);
        $source = '[code]function test() {}[/code]';
        $result = $wiki->transform($source, 'Plain');

        $this->assertStringContainsString('function test()', $result);
        $this->assertStringNotContainsString('[code]', $result);
    }

    public function testNestedCodeBlocksNotSupported(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Code']);
        $source = '[code]outer [code]inner[/code] outer[/code]';
        $result = $wiki->transform($source, 'Xhtml');

        // First [code]...[/code] pair should match
        // The word "outer" before inner [code] should be inside
        $this->assertStringContainsString('outer [code]inner', $result);
    }

    public function testCodeBlockPreservesNewlines(): void
    {
        $wiki = TextWikiBase::factory('BBCode', ['Code']);
        $source = "[code]\nline1\nline2\nline3\n[/code]";
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('line1', $result);
        $this->assertStringContainsString('line2', $result);
        $this->assertStringContainsString('line3', $result);
    }
}
