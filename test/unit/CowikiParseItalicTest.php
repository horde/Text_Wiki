<?php

declare(strict_types=1);

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use Horde\Text\Wiki\CowikiParserItalic;
use Horde\Text\Wiki\XhtmlRendererItalic;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Test Cowiki italic parsing (/italic text/)
 *
 * Note: Cowiki uses single forward slash for italic which is unusual
 * Parser uses negative lookbehind (?<!<) to avoid matching HTML tag slashes
 */
#[CoversClass(CowikiParserItalic::class)]
#[CoversClass(XhtmlRendererItalic::class)]
class CowikiParseItalicTest extends TestCase
{
    public function testSimpleItalic(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Italic']);
        $source = '/italic text/';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<i>', $result);
        $this->assertStringContainsString('italic text', $result);
        $this->assertStringContainsString('</i>', $result);
    }

    public function testItalicInSentence(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Italic']);
        $source = 'This is /italic/ text in a sentence.';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('This is', $result);
        $this->assertStringContainsString('<i>italic</i>', $result);
        $this->assertStringContainsString('text in a sentence', $result);
    }

    public function testMultipleItalic(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Italic']);
        $source = '/first/ and /second/ italic words';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<i>first</i>', $result);
        $this->assertStringContainsString('<i>second</i>', $result);
    }

    public function testItalicWithSpaces(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Italic']);
        $source = '/italic text with spaces/';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<i>italic text with spaces</i>', $result);
    }

    public function testItalicEmpty(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Italic']);
        $source = '//';
        $result = $wiki->transform($source, 'Xhtml');

        // Empty italic should still create tags
        $this->assertStringContainsString('<i></i>', $result);
    }

    public function testItalicMultiLine(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Italic']);
        $source = "First line with /italic/\nSecond line with /more italic/";
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<i>italic</i>', $result);
        $this->assertStringContainsString('<i>more italic</i>', $result);
    }

    public function testItalicWithPunctuation(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Italic']);
        $source = '/Hello!/ and /world?/';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<i>Hello!</i>', $result);
        $this->assertStringContainsString('<i>world?</i>', $result);
    }

    public function testBoldAndItalicCombined(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Bold', 'Italic']);
        $source = 'Text with *bold* and /italic/ formatting';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<b>bold</b>', $result);
        $this->assertStringContainsString('<i>italic</i>', $result);
    }

    public function testNestedBoldItalic(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Bold', 'Italic']);
        $source = '*/bold and italic/*';
        $result = $wiki->transform($source, 'Xhtml');

        // Should contain both bold and italic tags (order may vary)
        $this->assertStringContainsString('<b>', $result);
        $this->assertStringContainsString('<i>', $result);
        $this->assertStringContainsString('bold and italic', $result);
    }

    public function testItalicRenderToPlain(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Italic']);
        $source = 'This is /italic/ text';
        $result = $wiki->transform($source, 'Plain');

        // Plain text should strip formatting but keep content
        $this->assertStringContainsString('italic', $result);
        $this->assertStringNotContainsString('/', $result);
        $this->assertStringNotContainsString('<i>', $result);
    }
}
