<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test Tiki parser Bold formatting
 *
 * Tiki uses __text__ for bold
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class TikiParseBoldTest extends TestCase
{
    public function testTikiBoldParsing(): void
    {
        $wiki = TextWikiBase::factory('Tiki', ['Bold']);

        $input = "This is __bold text__ here.";
        $wiki->parse($input);

        $boldTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Bold');
        $this->assertGreaterThan(0, count($boldTokens));
    }

    public function testTikiBoldToXhtmlRendering(): void
    {
        $wiki = TextWikiBase::factory('Tiki', ['Bold']);

        $input = "Text with __bold__ here.";
        $output = $wiki->transform($input, 'Xhtml');

        $this->assertMatchesRegularExpression('/<(strong|b)>bold<\/(strong|b)>/', $output);
    }

    public function testMultipleBold(): void
    {
        $wiki = TextWikiBase::factory('Tiki', ['Bold']);

        $input = "__First__ and __second__ bold.";
        $wiki->parse($input);

        $boldTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Bold');
        $this->assertGreaterThanOrEqual(2, count($boldTokens));
    }
}
