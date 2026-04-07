<?php

declare(strict_types=1);

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use Horde\Text\Wiki\CowikiParserBold;
use Horde\Text\Wiki\XhtmlRendererBold;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Test Cowiki bold parsing (*bold text*)
 */
#[CoversClass(CowikiParserBold::class)]
#[CoversClass(XhtmlRendererBold::class)]
class CowikiParseBoldTest extends TestCase
{
    public function testSimpleBold(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Bold']);
        $source = '*bold text*';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<b>', $result);
        $this->assertStringContainsString('bold text', $result);
        $this->assertStringContainsString('</b>', $result);
    }

    public function testBoldInSentence(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Bold']);
        $source = 'This is *bold* text in a sentence.';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('This is', $result);
        $this->assertStringContainsString('<b>bold</b>', $result);
        $this->assertStringContainsString('text in a sentence', $result);
    }

    public function testMultipleBold(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Bold']);
        $source = '*first* and *second* bold words';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<b>first</b>', $result);
        $this->assertStringContainsString('<b>second</b>', $result);
    }

    public function testBoldWithSpaces(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Bold']);
        $source = '*bold text with spaces*';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<b>bold text with spaces</b>', $result);
    }

    public function testBoldEmpty(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Bold']);
        $source = '**';
        $result = $wiki->transform($source, 'Xhtml');

        // Empty bold should still create tags
        $this->assertStringContainsString('<b></b>', $result);
    }

    public function testNonMatchingAsterisks(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Bold']);
        $source = 'Text with * single asterisk';
        $result = $wiki->transform($source, 'Xhtml');

        // Single asterisk should remain literal (not matched by regex)
        // The regex requires paired asterisks
        $this->assertStringContainsString('*', $result);
    }

    public function testBoldMultiLine(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Bold']);
        $source = "First line with *bold*\nSecond line with *more bold*";
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<b>bold</b>', $result);
        $this->assertStringContainsString('<b>more bold</b>', $result);
    }

    public function testBoldWithPunctuation(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Bold']);
        $source = '*Hello!* and *world?*';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<b>Hello!</b>', $result);
        $this->assertStringContainsString('<b>world?</b>', $result);
    }

    public function testBoldRenderToPlain(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Bold']);
        $source = 'This is *bold* text';
        $result = $wiki->transform($source, 'Plain');

        // Plain text should strip formatting but keep content
        $this->assertStringContainsString('bold', $result);
        $this->assertStringNotContainsString('*', $result);
        $this->assertStringNotContainsString('<b>', $result);
    }
}
