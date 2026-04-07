<?php

declare(strict_types=1);

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\CowikiEngine;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Test Cowiki wikilink parsing
 *
 * Cowiki supports:
 * - CamelCase/StudlyCaps: WikiPageName
 * - Explicit links: [WikiPageName]
 * - Described links: [WikiPageName display text]
 * - Links with anchors: WikiPageName#section
 */
#[CoversClass(CowikiEngine::class)]
class CowikiParseWikilinkTest extends TestCase
{
    private CowikiEngine $wiki;

    protected function setUp(): void
    {
        $this->wiki = new CowikiEngine();
    }

    public function testSimpleCamelCase(): void
    {
        $source = 'See WikiPageName for details';
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<a', $result);
        $this->assertStringContainsString('WikiPageName', $result);
        $this->assertStringContainsString('</a>', $result);
    }

    public function testMultipleCamelCaseWords(): void
    {
        $source = 'Visit HomePage or AboutUs pages';
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('HomePage', $result);
        $this->assertStringContainsString('AboutUs', $result);
        // Should create two separate links
        $linkCount = substr_count($result, '<a');
        $this->assertGreaterThanOrEqual(2, $linkCount);
    }

    public function testExplicitWikiLink(): void
    {
        $source = '[WikiPage]';
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<a', $result);
        $this->assertStringContainsString('WikiPage', $result);
        $this->assertStringNotContainsString('[', $result);
        $this->assertStringNotContainsString(']', $result);
    }

    public function testDescribedWikiLink(): void
    {
        $source = '[WikiPageName link description here]';
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<a', $result);
        $this->assertStringContainsString('link description here', $result);
        // Page name should be in href or title, not visible text
        $this->assertStringContainsString('WikiPageName', $result);
    }

    public function testWikiLinkWithAnchor(): void
    {
        $source = 'Jump to WikiPage#section';
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<a', $result);
        $this->assertStringContainsString('WikiPage', $result);
        $this->assertStringContainsString('#section', $result);
    }

    public function testDescribedLinkWithAnchor(): void
    {
        $source = '[WikiPage#anchor link text]';
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<a', $result);
        $this->assertStringContainsString('link text', $result);
        $this->assertStringContainsString('WikiPage', $result);
        $this->assertStringContainsString('#anchor', $result);
    }

    public function testNotCamelCase(): void
    {
        $source = 'Normal words like wiki or page are not links';
        $result = $this->wiki->transform($source, 'Xhtml');

        // Should NOT create links for non-CamelCase words
        $this->assertStringNotContainsString('href', $result);
    }

    public function testCamelCaseInSentence(): void
    {
        $source = 'The WikiPage contains important information.';
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('The', $result);
        $this->assertStringContainsString('<a', $result);
        $this->assertStringContainsString('WikiPage', $result);
        $this->assertStringContainsString('contains important information', $result);
    }

    public function testWikiLinkRenderToPlain(): void
    {
        $source = 'See WikiPageName for more';
        $result = $this->wiki->transform($source, 'Plain');

        // Plain should preserve page name without HTML
        $this->assertStringContainsString('WikiPageName', $result);
        $this->assertStringNotContainsString('<a', $result);
    }

    public function testDescribedLinkRenderToPlain(): void
    {
        $source = '[WikiPage custom text]';
        $result = $this->wiki->transform($source, 'Plain');

        // Plain should show description text
        $this->assertStringContainsString('custom text', $result);
        $this->assertStringNotContainsString('[', $result);
        $this->assertStringNotContainsString(']', $result);
    }

    public function testMinimalCamelCase(): void
    {
        $source = 'Simple CamelCase like AB or XyZ';
        $result = $this->wiki->transform($source, 'Xhtml');

        // Minimal CamelCase (2 chars) should still create links
        $this->assertStringContainsString('href', $result);
    }

    public function testCamelCaseWithNumbers(): void
    {
        $source = 'Page Wiki2023 or Version3Page';
        $result = $this->wiki->transform($source, 'Xhtml');

        // CamelCase with numbers should create links
        $this->assertStringContainsString('Wiki2023', $result);
        $this->assertStringContainsString('Version3Page', $result);
    }
}
