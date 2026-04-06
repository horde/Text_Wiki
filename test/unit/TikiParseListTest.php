<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test Tiki parser List syntax
 *
 * Tiki uses * for unordered and # for ordered lists
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class TikiParseListTest extends TestCase
{
    public function testTikiUnorderedList(): void
    {
        $wiki = TextWikiBase::factory('Tiki', ['List']);

        $input = "* Item 1\n* Item 2\n* Item 3\n";
        $wiki->parse($input);

        $listTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'List');
        $this->assertGreaterThan(0, count($listTokens));
    }

    public function testTikiOrderedList(): void
    {
        $wiki = TextWikiBase::factory('Tiki', ['List']);

        $input = "# First\n# Second\n# Third\n";
        $wiki->parse($input);

        $listTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'List');
        $this->assertGreaterThan(0, count($listTokens));
    }

    public function testTikiNestedList(): void
    {
        $wiki = TextWikiBase::factory('Tiki', ['List']);

        $input = "* Level 1\n** Level 2\n*** Level 3\n";
        $wiki->parse($input);

        $listTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'List');
        $this->assertGreaterThan(0, count($listTokens));
    }

    public function testTikiListToXhtml(): void
    {
        $wiki = TextWikiBase::factory('Tiki', ['List']);

        $input = "* Item 1\n* Item 2\n";
        $output = $wiki->transform($input, 'Xhtml');

        $this->assertStringContainsString('<ul', $output);
        $this->assertStringContainsString('<li>', $output);
        $this->assertStringContainsString('Item 1', $output);
    }
}
