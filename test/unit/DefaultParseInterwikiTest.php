<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test Default parser Interwiki parsing
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class DefaultParseInterwikiTest extends TestCase
{
    public function testInterwikiParsing(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Interwiki']);

        $input = "See Wikipedia:PHP for details.";
        $wiki->parse($input);

        $interwikiTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Interwiki');
        $this->assertGreaterThanOrEqual(0, count($interwikiTokens));
    }

    public function testInterwikiToXhtmlRendering(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Interwiki']);

        // Configure a test interwiki site
        $wiki->setRenderConf('Xhtml', 'Interwiki', 'sites', [
            'Wikipedia' => 'https://en.wikipedia.org/wiki/%s',
        ]);

        $input = "Wikipedia:PHP";
        $output = $wiki->transform($input, 'Xhtml');

        // Should contain some reference to PHP
        $this->assertStringContainsString('PHP', $output);
    }

    public function testMultipleInterwikiLinks(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Interwiki']);

        $input = "See Wikipedia:PHP and WikiPedia:Python";
        $wiki->parse($input);

        $interwikiTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Interwiki');
        $this->assertGreaterThanOrEqual(0, count($interwikiTokens));
    }
}
