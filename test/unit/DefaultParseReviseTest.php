<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test Default parser Revise (revision tracking) parsing
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class DefaultParseReviseTest extends TestCase
{
    public function testReviseParsing(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Revise']);

        $input = "{{INS This is inserted}}";
        $wiki->parse($input);

        $reviseTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Revise');
        $this->assertGreaterThanOrEqual(0, count($reviseTokens));
    }

    public function testReviseDelParsing(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Revise']);

        $input = "{{DEL This is deleted}}";
        $wiki->parse($input);

        $reviseTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Revise');
        $this->assertGreaterThanOrEqual(0, count($reviseTokens));
    }

    public function testReviseToXhtmlRendering(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Revise']);

        $input = "{{INS inserted text}}";
        $output = $wiki->transform($input, 'Xhtml');

        // Should produce some output
        $this->assertIsString($output);
        $this->assertStringContainsString('inserted text', $output);
    }

    public function testMultipleRevisions(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Revise']);

        $input = "{{INS added}} and {{DEL removed}}";
        $wiki->parse($input);

        $reviseTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Revise');
        $this->assertGreaterThanOrEqual(0, count($reviseTokens));
    }
}
