<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test Tiki parser Table syntax
 *
 * Tiki uses || for tables
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class TikiParseTableTest extends TestCase
{
    public function testTikiSimpleTable(): void
    {
        $wiki = TextWikiBase::factory('Tiki', ['Table']);

        $input = "\n|| Cell1 | Cell2 ||\n";
        $wiki->parse($input);

        $tableTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Table');
        $this->assertGreaterThan(0, count($tableTokens));
    }

    public function testTikiTableToXhtml(): void
    {
        $wiki = TextWikiBase::factory('Tiki', ['Table']);

        $input = "\n|| Header1 | Header2 ||\n";
        $output = $wiki->transform($input, 'Xhtml');

        $this->assertStringContainsString('<table', $output);
        $this->assertStringContainsString('Header1', $output);
    }

    public function testTikiMultiRowTable(): void
    {
        $wiki = TextWikiBase::factory('Tiki', ['Table']);

        $input = "\n|| A | B || C | D || E | F ||\n";
        $wiki->parse($input);

        $tableTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Table');
        $this->assertGreaterThan(0, count($tableTokens));
    }
}
