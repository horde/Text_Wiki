<?php

declare(strict_types=1);

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\CowikiEngine;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Test Cowiki italic parsing (/italic text/)
 *
 * Note: Cowiki uses single forward slash for italic which is unusual
 * Parser uses negative lookbehind (?<!<) to avoid matching HTML tag slashes
 */
#[CoversClass(CowikiEngine::class)]
class CowikiParseItalicTest extends TestCase
{
    private CowikiEngine $wiki;

    protected function setUp(): void
    {
        $this->wiki = new CowikiEngine();
    }

    public function testSimpleItalic(): void
    {
        $source = '/italic text/';
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<em>', $result);
        $this->assertStringContainsString('italic text', $result);
        $this->assertStringContainsString('</em>', $result);
    }

    public function testItalicInSentence(): void
    {
        $source = 'This is /italic/ text in a sentence.';
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('This is', $result);
        $this->assertStringContainsString('<em>italic</em>', $result);
        $this->assertStringContainsString('text in a sentence', $result);
    }

    public function testMultipleItalic(): void
    {
        $source = '/first/ and /second/ italic words';
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<em>first</em>', $result);
        $this->assertStringContainsString('<em>second</em>', $result);
    }

    public function testItalicWithSpaces(): void
    {
        $source = '/italic text with spaces/';
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<em>italic text with spaces</em>', $result);
    }

    public function testItalicEmpty(): void
    {
        $source = '//';
        $result = $this->wiki->transform($source, 'Xhtml');

        // Empty italic should still create tags
        $this->assertStringContainsString('<em></em>', $result);
    }

    public function testItalicMultiLine(): void
    {
        $source = "First line with /italic/\nSecond line with /more italic/";
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<em>italic</em>', $result);
        $this->assertStringContainsString('<em>more italic</em>', $result);
    }

    public function testItalicWithPunctuation(): void
    {
        $source = '/Hello!/ and /world?/';
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<em>Hello!</em>', $result);
        $this->assertStringContainsString('<em>world?</em>', $result);
    }

    public function testBoldAndItalicCombined(): void
    {
        $source = 'Text with *bold* and /italic/ formatting';
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<strong>bold</strong>', $result);
        $this->assertStringContainsString('<em>italic</em>', $result);
    }

    public function testNestedBoldItalic(): void
    {
        $source = '*/bold and italic/*';
        $result = $this->wiki->transform($source, 'Xhtml');

        // Should contain both bold and italic tags (order may vary)
        $this->assertStringContainsString('<strong>', $result);
        $this->assertStringContainsString('<em>', $result);
        $this->assertStringContainsString('bold and italic', $result);
    }

    public function testItalicRenderToPlain(): void
    {
        $source = 'This is /italic/ text';
        $result = $this->wiki->transform($source, 'Plain');

        // Plain text should strip formatting but keep content
        $this->assertStringContainsString('italic', $result);
        $this->assertStringNotContainsString('/', $result);
        $this->assertStringNotContainsString('<em>', $result);
    }
}
