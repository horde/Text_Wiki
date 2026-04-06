<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test Default parser List parsing
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class DefaultParseListTest extends TestCase
{
    public function testUnorderedListParsing(): void
    {
        $wiki = TextWikiBase::factory('Default', ['List']);

        $input = "* Item 1\n";
        $input .= "* Item 2\n";
        $input .= "* Item 3\n";

        $wiki->parse($input);

        $this->assertGreaterThan(0, count($wiki->tokens));

        $listTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'List');
        $this->assertGreaterThan(0, count($listTokens));
    }

    public function testOrderedListParsing(): void
    {
        $wiki = TextWikiBase::factory('Default', ['List']);

        $input = "# First\n";
        $input .= "# Second\n";
        $input .= "# Third\n";

        $wiki->parse($input);

        $listTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'List');
        $this->assertGreaterThan(0, count($listTokens));
    }

    public function testNestedUnorderedList(): void
    {
        $wiki = TextWikiBase::factory('Default', ['List']);

        $input = "* Level 1\n";
        $input .= " * Level 2\n";
        $input .= "  * Level 3\n";
        $input .= " * Back to Level 2\n";
        $input .= "* Back to Level 1\n";

        $wiki->parse($input);

        $listTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'List');
        $this->assertGreaterThan(0, count($listTokens));

        // Check for different levels
        $levels = array_map(fn($t) => $t[1]['level'] ?? 0, $listTokens);
        $this->assertContains(1, $levels);
        $this->assertContains(2, $levels);
        $this->assertContains(3, $levels);
    }

    public function testNestedOrderedList(): void
    {
        $wiki = TextWikiBase::factory('Default', ['List']);

        $input = "# First\n";
        $input .= "## Nested first\n";
        $input .= "## Nested second\n";
        $input .= "# Second\n";

        $wiki->parse($input);

        $listTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'List');
        $this->assertGreaterThan(0, count($listTokens));
    }

    public function testMixedList(): void
    {
        $wiki = TextWikiBase::factory('Default', ['List']);

        $input = "* Unordered\n";
        $input .= "## Ordered nested\n";
        $input .= "## Ordered nested 2\n";
        $input .= "* Unordered again\n";

        $wiki->parse($input);

        $listTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'List');
        $this->assertGreaterThan(0, count($listTokens));
    }

    public function testListToXhtmlRendering(): void
    {
        $wiki = TextWikiBase::factory('Default', ['List']);

        $input = "* Item 1\n";
        $input .= "* Item 2\n";

        $output = $wiki->transform($input, 'Xhtml');

        $this->assertStringContainsString('<ul', $output);
        $this->assertStringContainsString('<li>', $output);
        $this->assertStringContainsString('</li>', $output);
        $this->assertStringContainsString('</ul>', $output);
        $this->assertStringContainsString('Item 1', $output);
    }

    public function testOrderedListToXhtml(): void
    {
        $wiki = TextWikiBase::factory('Default', ['List']);

        $input = "# First\n";
        $input .= "# Second\n";

        $output = $wiki->transform($input, 'Xhtml');

        $this->assertStringContainsString('<ol', $output);
        $this->assertStringContainsString('<li>', $output);
        $this->assertStringContainsString('</ol>', $output);
        $this->assertStringContainsString('First', $output);
    }

    public function testListItemWithMultipleLines(): void
    {
        $wiki = TextWikiBase::factory('Default', ['List']);

        $input = "* First item\ncontinued on next line\n";
        $input .= "* Second item\n";

        $wiki->parse($input);

        $listTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'List');
        $this->assertGreaterThan(0, count($listTokens));
    }
}
