<?php

declare(strict_types=1);

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test Cowiki bold parsing (*bold text*)
 */
#[CoversNothing]
class CowikiParseBoldTest extends TestCase
{
    public function testSimpleBold(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Bold']);
        $source = '*bold text*';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<strong>', $result);
        $this->assertStringContainsString('bold text', $result);
        $this->assertStringContainsString('</strong>', $result);
    }

    public function testBoldInSentence(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Bold']);
        $source = 'This is *bold* text in a sentence.';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('This is', $result);
        $this->assertStringContainsString('<strong>bold</strong>', $result);
        $this->assertStringContainsString('text in a sentence', $result);
    }

    public function testMultipleBold(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Bold']);
        $source = '*first* and *second* bold words';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<strong>first</strong>', $result);
        $this->assertStringContainsString('<strong>second</strong>', $result);
    }

    public function testBoldWithSpaces(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Bold']);
        $source = '*bold text with spaces*';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<strong>bold text with spaces</strong>', $result);
    }

    public function testBoldEmpty(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Bold']);
        $source = '**';
        $result = $wiki->transform($source, 'Xhtml');

        // Empty bold should still create tags
        $this->assertStringContainsString('<strong></strong>', $result);
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

        $this->assertStringContainsString('<strong>bold</strong>', $result);
        $this->assertStringContainsString('<strong>more bold</strong>', $result);
    }

    public function testBoldWithPunctuation(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Bold']);
        $source = '*Hello!* and *world?*';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<strong>Hello!</strong>', $result);
        $this->assertStringContainsString('<strong>world?</strong>', $result);
    }

    public function testBoldRenderToPlain(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Bold']);
        $source = 'This is *bold* text';
        $result = $wiki->transform($source, 'Plain');

        // Plain text should strip formatting but keep content
        $this->assertStringContainsString('bold', $result);
        $this->assertStringNotContainsString('*', $result);
        $this->assertStringNotContainsString('<strong>', $result);
    }
}
