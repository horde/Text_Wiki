<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test Default parser Phplookup parsing
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class DefaultParsePhplookupTest extends TestCase
{
    public function testPhplookupParsing(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Phplookup']);

        $input = "Use [[php array_map]] function.";
        $wiki->parse($input);

        $phplookupTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Phplookup');
        $this->assertGreaterThanOrEqual(0, count($phplookupTokens));
    }

    public function testPhplookupToXhtmlRendering(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Phplookup']);

        $input = "[[php array_map]]";
        $output = $wiki->transform($input, 'Xhtml');

        // Should contain reference to the function
        $this->assertStringContainsString('array_map', $output);
    }

    public function testMultiplePhplookups(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Phplookup']);

        $input = "[[php array_map]] and [[php array_filter]]";
        $wiki->parse($input);

        $phplookupTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Phplookup');
        $this->assertGreaterThanOrEqual(0, count($phplookupTokens));
    }
}
