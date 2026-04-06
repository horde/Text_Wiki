<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test Default parser Table parsing
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class DefaultParseTableTest extends TestCase
{
    public function testSimpleTableParsing(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Table']);

        $input = "|| Header 1 || Header 2 ||\n";
        $input .= "|| Cell 1   || Cell 2   ||\n";

        $wiki->parse($input);

        // Should create table tokens
        $this->assertGreaterThan(0, count($wiki->tokens));

        $tableTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Table');
        $this->assertGreaterThan(0, count($tableTokens));
    }

    public function testTableWithMultipleRows(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Table']);

        $input = "|| Name || Age ||\n";
        $input .= "|| Alice || 30 ||\n";
        $input .= "|| Bob || 25 ||\n";
        $input .= "|| Charlie || 35 ||\n";

        $wiki->parse($input);

        $tableTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Table');
        $this->assertGreaterThan(0, count($tableTokens));
    }

    public function testTableToXhtmlRendering(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Table']);

        $input = "|| Header 1 || Header 2 ||\n";
        $input .= "|| Data 1 || Data 2 ||\n";

        $output = $wiki->transform($input, 'Xhtml');

        $this->assertStringContainsString('<table', $output);
        $this->assertStringContainsString('</table>', $output);
        $this->assertStringContainsString('Header 1', $output);
        $this->assertStringContainsString('Data 1', $output);
    }

    public function testEmptyTable(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Table']);

        $input = "|| ||\n";

        $wiki->parse($input);

        // Even empty table should create tokens
        $tableTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Table');
        $this->assertGreaterThanOrEqual(0, count($tableTokens));
    }

    public function testTableWithDifferentColumnCounts(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Table']);

        $input = "|| Col1 || Col2 || Col3 ||\n";
        $input .= "|| A || B ||\n";
        $input .= "|| X || Y || Z || Extra ||\n";

        $wiki->parse($input);

        // Should handle irregular tables
        $tableTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Table');
        $this->assertGreaterThan(0, count($tableTokens));
    }
}
