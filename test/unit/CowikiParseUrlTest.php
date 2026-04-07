<?php

declare(strict_types=1);

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\CowikiEngine;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Test Cowiki URL parsing
 *
 * Cowiki supports:
 * - Inline URLs: http://example.com
 * - Described links: ((http://example.com)(Description))
 */
#[CoversClass(CowikiEngine::class)]
class CowikiParseUrlTest extends TestCase
{
    private CowikiEngine $wiki;

    protected function setUp(): void
    {
        $this->wiki = new CowikiEngine();
    }

    public function testSimpleInlineUrl(): void
    {
        $source = 'Visit http://example.com for more info';
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<a', $result);
        $this->assertStringContainsString('href="http://example.com"', $result);
        $this->assertStringContainsString('</a>', $result);
    }

    public function testHttpsUrl(): void
    {
        $source = 'Secure site https://example.com/page';
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('href="https://example.com/page"', $result);
    }

    public function testFtpUrl(): void
    {
        $source = 'Download from ftp://ftp.example.com/file.zip';
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('href="ftp://ftp.example.com/file.zip"', $result);
    }

    public function testMailtoUrl(): void
    {
        $source = 'Contact mailto:user@example.com';
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('href="mailto:user@example.com"', $result);
    }

    public function testDescribedLink(): void
    {
        $source = '((http://example.com)(Example Website))';
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('href="http://example.com"', $result);
        $this->assertStringContainsString('Example Website', $result);
        $this->assertStringNotContainsString('((', $result);
        $this->assertStringNotContainsString('))', $result);
    }

    public function testDescribedLinkWithoutText(): void
    {
        $source = '((http://example.com))';
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('href="http://example.com"', $result);
        // URL should be used as link text when description is missing
        $this->assertStringContainsString('http://example.com', $result);
    }

    public function testMultipleUrls(): void
    {
        $source = 'Visit http://example.com and https://example.org';
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('href="http://example.com"', $result);
        $this->assertStringContainsString('href="https://example.org"', $result);
    }

    public function testUrlWithQueryString(): void
    {
        $source = 'Search http://example.com/search?q=test&lang=en';
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('href="http://example.com/search?q=test&amp;lang=en"', $result);
    }

    public function testUrlWithAnchor(): void
    {
        $source = 'Jump to http://example.com/page#section';
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('href="http://example.com/page#section"', $result);
    }

    public function testUrlAtStartOfLine(): void
    {
        $source = "http://example.com\nSome text";
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('href="http://example.com"', $result);
    }

    public function testUrlAtEndOfSentence(): void
    {
        $source = 'Visit http://example.com.';
        $result = $this->wiki->transform($source, 'Xhtml');

        // Period should not be part of URL
        $this->assertStringContainsString('href="http://example.com"', $result);
        $this->assertStringContainsString('</a>.', $result);
    }

    public function testDescribedLinkComplex(): void
    {
        $source = '((https://example.com/path/to/page?id=123)(Complex URL Example))';
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('href="https://example.com/path/to/page?id=123"', $result);
        $this->assertStringContainsString('Complex URL Example', $result);
    }

    public function testUrlRenderToPlain(): void
    {
        $source = 'Visit http://example.com for more';
        $result = $this->wiki->transform($source, 'Plain');

        // Plain should preserve URL without HTML
        $this->assertStringContainsString('http://example.com', $result);
        $this->assertStringNotContainsString('<a', $result);
    }

    public function testDescribedLinkRenderToPlain(): void
    {
        $source = '((http://example.com)(Example Site))';
        $result = $this->wiki->transform($source, 'Plain');

        // Plain should show both URL and description
        $this->assertStringContainsString('Example Site', $result);
        $this->assertStringContainsString('http://example.com', $result);
    }

    public function testUrlInParentheses(): void
    {
        $source = 'Link (http://example.com) in parentheses';
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('href="http://example.com"', $result);
        // Closing paren should not be part of URL
        $this->assertStringNotContainsString('http://example.com)', $result);
    }
}
