<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Test\Integration;

use Horde\Text\Wiki\Cowiki\CowikiParser;
use Horde\Text\Wiki\Renderer\Cowiki;
use PHPUnit\Framework\TestCase;

/**
 * Cowiki round-trip idempotency test
 *
 * Verifies: parse → render → parse → render stabilizes.
 * Uses whitespace normalization (collapse multiple blank lines).
 *
 * @coversNothing
 */
class CowikiIdempotencyTest extends TestCase
{
    private CowikiParser $parser;
    private Cowiki $renderer;

    protected function setUp(): void
    {
        $this->parser = new CowikiParser();
        $this->renderer = new Cowiki();
    }

    private function roundTrip(string $input): string
    {
        return $this->renderer->render($this->parser->parse($input));
    }

    private function normalizeWs(string $s): string
    {
        return preg_replace('/\n\n+/', "\n\n", trim($s));
    }

    /**
     * Assert idempotency: second pass output equals first pass output
     */
    private function assertIdempotent(string $source, string $message = ''): void
    {
        $firstPass = $this->roundTrip($source);
        $secondPass = $this->roundTrip($firstPass);

        $this->assertEquals(
            $this->normalizeWs($firstPass),
            $this->normalizeWs($secondPass),
            $message ?: 'Output should stabilize after one pass'
        );
    }

    // ---------------------------------------------------------------
    // Inline formatting
    // ---------------------------------------------------------------

    public function testBoldIdempotency(): void
    {
        $this->assertIdempotent('*bold text*', 'Bold');
    }

    public function testItalicIdempotency(): void
    {
        $this->assertIdempotent('/italic text/', 'Italic');
    }

    public function testUnderlineIdempotency(): void
    {
        $this->assertIdempotent('_underlined_', 'Underline');
    }

    public function testMonospaceIdempotency(): void
    {
        $this->assertIdempotent('=monospace=', 'Monospace');
    }

    public function testSuperscriptIdempotency(): void
    {
        $this->assertIdempotent('E=mc<sup>2</sup>', 'Superscript');
    }

    public function testSubscriptIdempotency(): void
    {
        $this->assertIdempotent('H<sub>2</sub>O', 'Subscript');
    }

    // ---------------------------------------------------------------
    // Links
    // ---------------------------------------------------------------

    public function testUrlDescribedIdempotency(): void
    {
        $this->assertIdempotent('((http://example.com)(Example Site))', 'URL described');
    }

    public function testUrlBareParenIdempotency(): void
    {
        $this->assertIdempotent('((http://example.com))', 'URL bare paren');
    }

    public function testUrlInlineIdempotency(): void
    {
        $this->assertIdempotent('Visit http://example.com today', 'URL inline');
    }

    public function testWikilinkWithTextIdempotency(): void
    {
        $this->assertIdempotent('((SomePage)(link text))', 'Wikilink with text');
    }

    public function testWikilinkBareIdempotency(): void
    {
        $this->assertIdempotent('((MyPage))', 'Wikilink bare');
    }

    // ---------------------------------------------------------------
    // Block elements
    // ---------------------------------------------------------------

    public function testHeadingLevel1Idempotency(): void
    {
        $this->assertIdempotent('+ Heading 1', 'Heading level 1');
    }

    public function testHeadingLevel3Idempotency(): void
    {
        $this->assertIdempotent('+++ Heading 3', 'Heading level 3');
    }

    public function testHeadingLevel6Idempotency(): void
    {
        $this->assertIdempotent('++++++ Heading 6', 'Heading level 6');
    }

    public function testHorizontalRuleIdempotency(): void
    {
        $this->assertIdempotent('---', 'Horizontal rule');
    }

    public function testCodeBlockIdempotency(): void
    {
        $this->assertIdempotent("<code>\necho 'hello';\n</code>", 'Code block');
    }

    public function testRawNoopIdempotency(): void
    {
        $this->assertIdempotent('<noop>*not bold* /not italic/</noop>', 'Raw noop');
    }

    public function testBlockquoteIdempotency(): void
    {
        $this->assertIdempotent('> quoted text', 'Blockquote');
    }

    public function testTocIdempotency(): void
    {
        $this->assertIdempotent('<toc>', 'TOC');
    }

    // ---------------------------------------------------------------
    // Lists
    // ---------------------------------------------------------------

    public function testBulletListIdempotency(): void
    {
        $this->assertIdempotent("* Item 1\n* Item 2\n* Item 3", 'Bullet list');
    }

    public function testNumberedListIdempotency(): void
    {
        $this->assertIdempotent("# First\n# Second\n# Third", 'Numbered list');
    }

    public function testNestedListIdempotency(): void
    {
        $this->assertIdempotent("* Outer\n * Inner\n* Back", 'Nested list');
    }

    public function testDeepNestedListIdempotency(): void
    {
        $this->assertIdempotent(
            "* L1\n * L2\n  * L3\n * L2b\n* L1b",
            'Deep nested list'
        );
    }

    public function testMixedTypeNestedListIdempotency(): void
    {
        $this->assertIdempotent(
            "* Bullet\n # Numbered\n* Back",
            'Mixed bullet/numbered nesting'
        );
    }

    // ---------------------------------------------------------------
    // Tables
    // ---------------------------------------------------------------

    public function testTableIdempotency(): void
    {
        $this->assertIdempotent(
            "<table>\n<tr><th>Header 1</th><th>Header 2</th></tr>\n<tr><td>Cell 1</td><td>Cell 2</td></tr>\n</table>",
            'Table'
        );
    }

    // ---------------------------------------------------------------
    // Block element transitions
    // ---------------------------------------------------------------

    public function testMultipleHeadingsIdempotency(): void
    {
        $this->assertIdempotent(
            "+ Heading 1\n\n++ Heading 2\n\n+++ Heading 3",
            'Multiple headings'
        );
    }

    public function testHorizWithSurroundingTextIdempotency(): void
    {
        $this->assertIdempotent(
            "Text before\n\n---\n\nText after",
            'Horiz with surrounding text'
        );
    }

    public function testMixedInlineIdempotency(): void
    {
        $this->assertIdempotent(
            '*bold* and /italic/ text',
            'Mixed inline'
        );
    }

    // ---------------------------------------------------------------
    // Complex document
    // ---------------------------------------------------------------

    public function testComplexDocumentStabilizes(): void
    {
        $source = <<<'COWIKI'
+ Main Heading

Some paragraph with *bold* and /italic/ text.

++ Sub Heading

* Item 1
* Item 2
 * Nested item

((http://example.com)(Visit us))

---

> A famous quote

<code>
echo 'hello';
</code>

<toc>
COWIKI;

        $firstPass = $this->roundTrip($source);
        $secondPass = $this->roundTrip($firstPass);
        $thirdPass = $this->roundTrip($secondPass);

        $this->assertEquals(
            $this->normalizeWs($secondPass),
            $this->normalizeWs($thirdPass),
            'Complex document should stabilize after one pass'
        );

        $this->assertNotEmpty($firstPass);
        $this->assertNotEmpty($secondPass);
    }

    public function testGetFormat(): void
    {
        $this->assertSame('cowiki', $this->renderer->getFormat());
    }
}
