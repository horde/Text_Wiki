<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test Tiki parser URL syntax
 *
 * Tiki uses [http://url] or [http://url|label]
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class TikiParseUrlTest extends TestCase
{
    public function testTikiSimpleUrl(): void
    {
        $wiki = TextWikiBase::factory('Tiki', ['Url']);

        $input = "[http://example.com]";
        $wiki->parse($input);

        $urlTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Url');
        $this->assertGreaterThan(0, count($urlTokens));
    }

    public function testTikiUrlWithLabel(): void
    {
        $wiki = TextWikiBase::factory('Tiki', ['Url']);

        $input = "[http://example.com|Click Here]";
        $wiki->parse($input);

        $urlTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Url');
        $this->assertGreaterThan(0, count($urlTokens));
    }

    public function testTikiInlineUrl(): void
    {
        $wiki = TextWikiBase::factory('Tiki', ['Url']);

        $input = "Visit http://example.com today.";
        $wiki->parse($input);

        $urlTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Url');
        $this->assertGreaterThan(0, count($urlTokens));
    }

    public function testTikiUrlToXhtml(): void
    {
        $wiki = TextWikiBase::factory('Tiki', ['Url']);

        $input = "[http://example.com|Example]";
        $output = $wiki->transform($input, 'Xhtml');

        $this->assertStringContainsString('<a', $output);
        $this->assertStringContainsString('http://example.com', $output);
        $this->assertStringContainsString('Example', $output);
    }
}
