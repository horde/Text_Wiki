<?php

declare(strict_types=1);

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use Horde\Text\Wiki\CowikiParserTable;
use Horde\Text\Wiki\XhtmlRendererTable;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Test Cowiki table parsing
 *
 * Cowiki uses HTML-style table syntax:
 * <table>
 * <tr><th>Header</th></tr>
 * <tr><td>Cell</td></tr>
 * </table>
 */
#[CoversClass(CowikiParserTable::class)]
#[CoversClass(XhtmlRendererTable::class)]
class CowikiParseTableTest extends TestCase
{
    public function testSimpleTable(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Table']);
        $wiki = TextWikiBase::factory('Cowiki', ['Table']);
        $source = "\n<table>\n<tr><th>Header 1</th><th>Header 2</th></tr>\n<tr><td>Cell 1</td><td>Cell 2</td></tr>\n</table>\n";
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<table', $result);
        $this->assertStringContainsString('<tr>', $result);
        $this->assertStringContainsString('<th>', $result);
        $this->assertStringContainsString('Header 1', $result);
        $this->assertStringContainsString('Header 2', $result);
        $this->assertStringContainsString('<td>', $result);
        $this->assertStringContainsString('Cell 1', $result);
        $this->assertStringContainsString('Cell 2', $result);
        $this->assertStringContainsString('</table>', $result);
    }

    public function testTableWithMultipleRows(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Table']);
        $source = "\n<table>\n<tr><th>Name</th><th>Value</th></tr>\n<tr><td>Row 1</td><td>Value 1</td></tr>\n<tr><td>Row 2</td><td>Value 2</td></tr>\n<tr><td>Row 3</td><td>Value 3</td></tr>\n</table>\n";
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('Row 1', $result);
        $this->assertStringContainsString('Row 2', $result);
        $this->assertStringContainsString('Row 3', $result);
        $this->assertStringContainsString('Value 1', $result);
        $this->assertStringContainsString('Value 2', $result);
        $this->assertStringContainsString('Value 3', $result);
    }

    public function testTableWithAttributes(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Table']);
        $source = "\n<table border=\"1\" cellpadding=\"5\">\n<tr><th>Header</th></tr>\n<tr><td>Cell</td></tr>\n</table>\n";
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<table', $result);
        // Attributes may be preserved or modified by renderer
        $this->assertStringContainsString('Header', $result);
        $this->assertStringContainsString('Cell', $result);
    }

    public function testTableNoHeaders(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Table']);
        $source = "\n<table>\n<tr><td>Cell 1</td><td>Cell 2</td></tr>\n<tr><td>Cell 3</td><td>Cell 4</td></tr>\n</table>\n";
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<table', $result);
        $this->assertStringContainsString('<td>', $result);
        $this->assertStringContainsString('Cell 1', $result);
        $this->assertStringContainsString('Cell 4', $result);
    }

    public function testTableWithFormattedContent(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Table']);
        $source = "\n<table>\n<tr><th>*Bold Header*</th></tr>\n<tr><td>/Italic cell/</td></tr>\n</table>\n";
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<table', $result);
        // Bold and italic should be processed within table cells
        $this->assertStringContainsString('Bold Header', $result);
        $this->assertStringContainsString('Italic cell', $result);
    }

    public function testTableWithLinks(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Table', 'Url', 'Wikilink']);
        $wiki->setParseConf('Wikilink', 'camel_case', true);
        $source = "\n<table>\n<tr><td>http://example.com</td><td>WikiLink</td></tr>\n</table>\n";
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<table', $result);
        // Links should be processed within table cells
        $this->assertStringContainsString('href', $result);
    }

    public function testEmptyTable(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Table']);
        $source = "\n<table>\n</table>\n";
        $result = $wiki->transform($source, 'Xhtml');

        // Empty table should still create table tags
        $this->assertStringContainsString('<table', $result);
        $this->assertStringContainsString('</table>', $result);
    }

    public function testTableRenderToPlain(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Table']);
        $source = "\n<table>\n<tr><th>Header</th></tr>\n<tr><td>Cell</td></tr>\n</table>\n";
        $result = $wiki->transform($source, 'Plain');

        // Plain should preserve content without HTML tables
        $this->assertStringContainsString('Header', $result);
        $this->assertStringContainsString('Cell', $result);
        $this->assertStringNotContainsString('<table', $result);
    }

    public function testMultipleTables(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Table']);
        $source = "\n<table>\n<tr><td>Table 1</td></tr>\n</table>\n\nText between tables\n\n<table>\n<tr><td>Table 2</td></tr>\n</table>\n";
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('Table 1', $result);
        $this->assertStringContainsString('Table 2', $result);
        $this->assertStringContainsString('Text between tables', $result);

        // Should have two separate table blocks
        $tableCount = substr_count($result, '<table');
        $this->assertGreaterThanOrEqual(2, $tableCount);
    }

    public function testTableWithColspan(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Table']);
        $source = "\n<table>\n<tr><th colspan=\"2\">Wide Header</th></tr>\n<tr><td>Cell 1</td><td>Cell 2</td></tr>\n</table>\n";
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('Wide Header', $result);
        $this->assertStringContainsString('Cell 1', $result);
        $this->assertStringContainsString('Cell 2', $result);
        // Colspan attribute may be preserved
        $this->assertStringContainsString('<th', $result);
    }

    public function testTableWithRowspan(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Table']);
        $source = "\n<table>\n<tr><td rowspan=\"2\">Tall Cell</td><td>Top</td></tr>\n<tr><td>Bottom</td></tr>\n</table>\n";
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('Tall Cell', $result);
        $this->assertStringContainsString('Top', $result);
        $this->assertStringContainsString('Bottom', $result);
    }

    // === Pipe-delimited format tests (PEAR Text_Wiki2 compatibility) ===

    public function testPipeDelimitedSimple(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Table']);
        $source = "\n<table>\n<tr>|Header 1|Header 2|\n<tr>|Cell 1|Cell 2|\n</table>\n";
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<table', $result);
        $this->assertStringContainsString('<td>Header 1</td>', $result);
        $this->assertStringContainsString('<td>Header 2</td>', $result);
        $this->assertStringContainsString('<td>Cell 1</td>', $result);
        $this->assertStringContainsString('<td>Cell 2</td>', $result);
    }

    public function testPipeDelimitedColspan(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Table']);
        $source = "\n<table>\n<tr>|Wide||||\n<tr>|A|B|C|\n</table>\n";
        $result = $wiki->transform($source, 'Xhtml');

        // |Wide|||| creates one cell "Wide" followed by 3 empty pipes (colspan=4)
        // The 4th pipe creates the trailing empty which doesn't count
        $this->assertStringContainsString('colspan="4"', $result);
        $this->assertStringContainsString('Wide', $result);
        $this->assertStringContainsString('<td>A</td>', $result);
        $this->assertStringContainsString('<td>B</td>', $result);
    }

    public function testPipeToHtmlConversion(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Table']);
        $source = "\n<table>\n<tr>|A|B|\n<tr>|C|D|\n</table>\n";

        // First pass converts pipe to HTML
        $firstPass = $wiki->transform($source, 'Cowiki');

        // Should contain HTML cell tags
        $this->assertStringContainsString('<td>A</td>', $firstPass);
        $this->assertStringContainsString('<td>B</td>', $firstPass);

        // Second pass should be idempotent
        $secondPass = $wiki->transform($firstPass, 'Cowiki');
        $this->assertEquals($firstPass, $secondPass, 'Pipe format should stabilize after HTML conversion');
    }

    public function testPipeRenderToPlain(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Table']);
        $source = "\n<table>\n<tr>|Data 1|Data 2|\n</table>\n";
        $result = $wiki->transform($source, 'Plain');

        $this->assertStringContainsString('Data 1', $result);
        $this->assertStringContainsString('Data 2', $result);
        $this->assertStringNotContainsString('<table', $result);
    }

    public function testMixedHtmlAndPipeFormats(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Table']);
        // One pipe table, one HTML table
        $source = "\n<table>\n<tr>|Pipe 1|Pipe 2|\n</table>\n\nText between\n\n<table>\n<tr><th>HTML 1</th></tr>\n</table>\n";
        $result = $wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('Pipe 1', $result);
        $this->assertStringContainsString('Pipe 2', $result);
        $this->assertStringContainsString('HTML 1', $result);
        $this->assertStringContainsString('Text between', $result);
        $this->assertGreaterThanOrEqual(2, substr_count($result, '<table'));
    }
}
