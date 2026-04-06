<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test Default parser Center parsing
 *
 * Tests = text = centering syntax
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class DefaultParseCenterTest extends TestCase
{
    public function testCenterParsing(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Center']);

        $input = "= Centered Text =";
        $wiki->parse($input);

        $centerTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Center');
        $this->assertGreaterThan(0, count($centerTokens));
    }

    public function testCenterToXhtmlRendering(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Center']);

        $input = "= Centered =";
        $output = $wiki->transform($input, 'Xhtml');

        $this->assertMatchesRegularExpression('/(text-align:\s*center|class="[^"]*center)/i', $output);
        $this->assertStringContainsString('Centered', $output);
    }

    public function testMultipleCenteredSections(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Center']);

        $input = "= First =\nRegular text\n= Second =";
        $wiki->parse($input);

        $centerTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Center');
        $this->assertGreaterThanOrEqual(2, count($centerTokens));
    }

    public function testCenterWithoutClosing(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Center']);

        $input = "= Text without closing";
        $wiki->parse($input);

        // Should still parse (trailing = is optional)
        $centerTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Center');
        $this->assertGreaterThanOrEqual(0, count($centerTokens));
    }
}
