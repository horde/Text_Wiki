<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Test\Integration;

use Horde\Text\Wiki\Doku\DokuParser;
use Horde\Text\Wiki\Renderer\Doku;
use PHPUnit\Framework\TestCase;

/**
 * DokuWiki round-trip idempotency test
 *
 * Verifies: parse -> render -> parse -> render stabilizes.
 * Uses whitespace normalization (collapse multiple blank lines).
 *
 * @coversNothing
 */
class DokuIdempotencyTest extends TestCase
{
    private DokuParser $parser;
    private Doku $renderer;

    protected function setUp(): void
    {
        $this->parser = new DokuParser();
        $this->renderer = new Doku();
    }

    private function roundTrip(string $input): string
    {
        return $this->renderer->render($this->parser->parse($input));
    }

    private function normalizeWs(string $s): string
    {
        return preg_replace('/\n\n+/', "\n\n", trim($s));
    }

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
        $this->assertIdempotent('**bold text**', 'Bold');
    }

    public function testItalicIdempotency(): void
    {
        $this->assertIdempotent('//italic text//', 'Italic');
    }

    public function testUnderlineIdempotency(): void
    {
        $this->assertIdempotent('__underlined__', 'Underline');
    }

    public function testMonospaceIdempotency(): void
    {
        $this->assertIdempotent("''monospace''", 'Monospace');
    }

    public function testSuperscriptIdempotency(): void
    {
        $this->assertIdempotent('E=mc<sup>2</sup>', 'Superscript');
    }

    public function testSubscriptIdempotency(): void
    {
        $this->assertIdempotent('H<sub>2</sub>O', 'Subscript');
    }

    public function testStrikethroughIdempotency(): void
    {
        $this->assertIdempotent('This is <del>deleted</del> text', 'Strikethrough');
    }

    // ---------------------------------------------------------------
    // Links
    // ---------------------------------------------------------------

    public function testUrlDescribedIdempotency(): void
    {
        $this->assertIdempotent('[[http://example.com|Example Site]]', 'URL described');
    }

    public function testUrlBareIdempotency(): void
    {
        $this->assertIdempotent('Visit http://example.com today', 'URL bare');
    }

    public function testWikilinkWithTextIdempotency(): void
    {
        $this->assertIdempotent('[[SomePage|link text]]', 'Wikilink with text');
    }

    public function testWikilinkBareIdempotency(): void
    {
        $this->assertIdempotent('[[MyPage]]', 'Wikilink bare');
    }

    public function testWikilinkWithAnchorIdempotency(): void
    {
        $this->assertIdempotent('[[SomePage#section|go to section]]', 'Wikilink with anchor');
    }

    // ---------------------------------------------------------------
    // Block elements
    // ---------------------------------------------------------------

    public function testHeadingLevel1Idempotency(): void
    {
        $this->assertIdempotent('====== Heading 1 ======', 'Heading level 1');
    }

    public function testHeadingLevel3Idempotency(): void
    {
        $this->assertIdempotent('==== Heading 3 ====', 'Heading level 3');
    }

    public function testHeadingLevel5Idempotency(): void
    {
        $this->assertIdempotent('== Heading 5 ==', 'Heading level 5');
    }

    public function testHorizontalRuleIdempotency(): void
    {
        $this->assertIdempotent('----', 'Horizontal rule');
    }

    public function testCodeBlockIdempotency(): void
    {
        $this->assertIdempotent("<code>\necho hello\n</code>", 'Code block');
    }

    public function testBlockquoteIdempotency(): void
    {
        $this->assertIdempotent('> quoted text', 'Blockquote');
    }

    public function testCenterIdempotency(): void
    {
        $this->assertIdempotent('::centered text::', 'Center');
    }

    // ---------------------------------------------------------------
    // Image
    // ---------------------------------------------------------------

    public function testImageIdempotency(): void
    {
        $this->assertIdempotent('{{http://example.com/img.png|Photo}}', 'Image');
    }

    public function testImageBareIdempotency(): void
    {
        $this->assertIdempotent('{{http://example.com/img.png}}', 'Image bare');
    }

    // ---------------------------------------------------------------
    // Lists (indentation-based)
    // ---------------------------------------------------------------

    public function testBulletListIdempotency(): void
    {
        $this->assertIdempotent("  * Item 1\n  * Item 2\n  * Item 3", 'Bullet list');
    }

    public function testNumberedListIdempotency(): void
    {
        $this->assertIdempotent("  - First\n  - Second\n  - Third", 'Numbered list');
    }

    public function testNestedListIdempotency(): void
    {
        $this->assertIdempotent("  * Outer\n    * Inner\n  * Back", 'Nested list');
    }

    public function testDeepNestedListIdempotency(): void
    {
        $this->assertIdempotent(
            "  * L1\n    * L2\n      * L3\n    * L2b\n  * L1b",
            'Deep nested list'
        );
    }

    // ---------------------------------------------------------------
    // Tables
    // ---------------------------------------------------------------

    public function testTableIdempotency(): void
    {
        $this->assertIdempotent(
            "^ Header 1 ^ Header 2 ^\n| Cell 1 | Cell 2 |",
            'Table'
        );
    }

    // ---------------------------------------------------------------
    // Definition lists
    // ---------------------------------------------------------------

    public function testDeflistIdempotency(): void
    {
        $this->assertIdempotent(
            "; Term ; Definition\n",
            'Definition list'
        );
    }

    // ---------------------------------------------------------------
    // Block element transitions
    // ---------------------------------------------------------------

    public function testMultipleHeadingsIdempotency(): void
    {
        $this->assertIdempotent(
            "====== Heading 1 ======\n\n===== Heading 2 =====\n\n==== Heading 3 ====",
            'Multiple headings'
        );
    }

    public function testHorizWithSurroundingTextIdempotency(): void
    {
        $this->assertIdempotent(
            "Text before\n\n----\n\nText after",
            'Horiz with surrounding text'
        );
    }

    public function testMixedInlineIdempotency(): void
    {
        $this->assertIdempotent(
            '**bold** and //italic// text',
            'Mixed inline'
        );
    }

    // ---------------------------------------------------------------
    // Complex document
    // ---------------------------------------------------------------

    public function testComplexDocumentStabilizes(): void
    {
        $source = <<<'DOKU'
            ====== Main Heading ======

            Some paragraph with **bold** text.

            ===== Sub Heading =====

              * Item 1
              * Item 2
                * Nested item

            [[http://example.com|Visit us]]

            ----

            > A famous quote

            <code>
            echo hello
            </code>
            DOKU;

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
        $this->assertSame('doku', $this->renderer->getFormat());
    }
}
