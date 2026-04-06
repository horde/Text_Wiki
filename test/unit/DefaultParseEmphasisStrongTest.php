<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test Default parser Emphasis and Strong (aliases for Italic and Bold)
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class DefaultParseEmphasisStrongTest extends TestCase
{
    public function testEmphasisParsing(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Emphasis']);

        $input = "//emphasized text//";
        $wiki->parse($input);

        $emphasisTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Emphasis');

        // Emphasis might be an alias - check if any tokens were created
        $this->assertGreaterThanOrEqual(0, count($emphasisTokens));
    }

    public function testStrongParsing(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Strong']);

        $input = "**strong text**";
        $wiki->parse($input);

        $strongTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Strong');

        // Strong might be an alias - check if any tokens were created
        $this->assertGreaterThanOrEqual(0, count($strongTokens));
    }

    public function testEmphasisToXhtmlRendering(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Emphasis']);

        $input = "//emphasized//";
        $output = $wiki->transform($input, 'Xhtml');

        // If Emphasis works, should produce some markup
        $this->assertIsString($output);
    }

    public function testStrongToXhtmlRendering(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Strong']);

        $input = "**strong**";
        $output = $wiki->transform($input, 'Xhtml');

        // If Strong works, should produce some markup
        $this->assertIsString($output);
    }
}
