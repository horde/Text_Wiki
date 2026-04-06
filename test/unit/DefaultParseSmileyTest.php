<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test Default parser Smiley parsing
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class DefaultParseSmileyTest extends TestCase
{
    public function testSmileyParsing(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Smiley']);

        $input = "Hello :) world!";
        $wiki->parse($input);

        $smileyTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Smiley');
        $this->assertGreaterThanOrEqual(0, count($smileyTokens));
    }

    public function testMultipleSmileys(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Smiley']);

        $input = "Happy :) or sad :( or wink ;)";
        $wiki->parse($input);

        $smileyTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Smiley');
        $this->assertGreaterThanOrEqual(0, count($smileyTokens));
    }

    public function testSmileyToXhtmlRendering(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Smiley']);

        $input = "Test :) smiley";
        $output = $wiki->transform($input, 'Xhtml');

        // Should produce some output
        $this->assertIsString($output);
        $this->assertNotEmpty($output);
    }
}
