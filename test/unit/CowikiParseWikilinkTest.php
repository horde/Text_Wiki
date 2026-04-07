<?php

declare(strict_types=1);

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use Horde\Text\Wiki\CowikiParserWikilink;
use Horde\Text\Wiki\XhtmlRendererWikilink;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Test Cowiki wikilink parsing
 *
 * Cowiki supports:
 * - CamelCase/StudlyCaps: WikiPageName (requires camel_case config)
 * - Described links: ((WikiPageName))
 * - Described links with text: ((WikiPageName)(display text))
 * - Links with anchors: ((WikiPageName#section))
 */
#[CoversClass(CowikiParserWikilink::class)]
#[CoversClass(XhtmlRendererWikilink::class)]
class CowikiParseWikilinkTest extends TestCase
{
    public function testSimpleCamelCase(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Wikilink']);
        $wiki->setParseConf('Wikilink', 'camel_case', true);
        $source = 'See WikiPageName for details';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<a', $result);
        $this->assertStringContainsString('WikiPageName', $result);
        $this->assertStringContainsString('</a>', $result);
    }

    public function testMultipleCamelCaseWords(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Wikilink']);
        $wiki->setParseConf('Wikilink', 'camel_case', true);
        $source = 'Visit HomePage or AboutUs pages';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('HomePage', $result);
        $this->assertStringContainsString('AboutUs', $result);
        // Should create two separate links
        $linkCount = substr_count($result, '<a');
        $this->assertGreaterThanOrEqual(2, $linkCount);
    }

    public function testExplicitWikiLink(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Wikilink']);
        $source = '((WikiPage))';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<a', $result);
        $this->assertStringContainsString('WikiPage', $result);
    }

    public function testDescribedWikiLink(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Wikilink']);
        $source = '((WikiPageName)(link description here))';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<a', $result);
        $this->assertStringContainsString('link description here', $result);
        // Page name should be in href or title, not visible text
        $this->assertStringContainsString('WikiPageName', $result);
    }

    public function testWikiLinkWithAnchor(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Wikilink']);
        $wiki->setParseConf('Wikilink', 'camel_case', true);
        $source = 'Jump to WikiPage#section';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<a', $result);
        $this->assertStringContainsString('WikiPage', $result);
        $this->assertStringContainsString('#section', $result);
    }

    public function testDescribedLinkWithAnchor(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Wikilink']);
        $source = '((WikiPage#anchor)(link text))';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<a', $result);
        $this->assertStringContainsString('link text', $result);
        $this->assertStringContainsString('WikiPage', $result);
        $this->assertStringContainsString('#anchor', $result);
    }

    public function testNotCamelCase(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Wikilink']);
        $wiki->setParseConf('Wikilink', 'camel_case', true);
        $source = 'Normal words like wiki or page are not links';
        $result = $wiki->transform($source, 'Xhtml');

        // Should NOT create links for non-CamelCase words
        $this->assertStringNotContainsString('href', $result);
    }

    public function testCamelCaseInSentence(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Wikilink']);
        $wiki->setParseConf('Wikilink', 'camel_case', true);
        $source = 'The WikiPage contains important information.';
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('The', $result);
        $this->assertStringContainsString('<a', $result);
        $this->assertStringContainsString('WikiPage', $result);
        $this->assertStringContainsString('contains important information', $result);
    }

    public function testWikiLinkRenderToPlain(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Wikilink']);
        $wiki->setParseConf('Wikilink', 'camel_case', true);
        $source = 'See WikiPageName for more';
        $result = $wiki->transform($source, 'Plain');

        // Plain should preserve page name without HTML
        $this->assertStringContainsString('WikiPageName', $result);
        $this->assertStringNotContainsString('<a', $result);
    }

    public function testDescribedLinkRenderToPlain(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Wikilink']);
        $source = '((WikiPage)(custom text))';
        $result = $wiki->transform($source, 'Plain');

        // Plain should show description text
        $this->assertStringContainsString('custom text', $result);
    }

    public function testMinimalCamelCase(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Wikilink']);
        $wiki->setParseConf('Wikilink', 'camel_case', true);
        $source = 'Simple CamelCase like AB or XyZ';
        $result = $wiki->transform($source, 'Xhtml');

        // Minimal CamelCase (2 chars) should still create links
        $this->assertStringContainsString('href', $result);
    }

    public function testCamelCaseWithNumbers(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Wikilink']);
        $wiki->setParseConf('Wikilink', 'camel_case', true);
        $source = 'Page Wiki2023 or Version3Page';
        $result = $wiki->transform($source, 'Xhtml');

        // CamelCase with numbers should create links
        $this->assertStringContainsString('Wiki2023', $result);
        $this->assertStringContainsString('Version3Page', $result);
    }
}
