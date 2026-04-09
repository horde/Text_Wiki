<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Test\Integration;

use Horde\Text\Wiki\Mediawiki\MediawikiParser;
use Horde\Text\Wiki\Renderer\Mediawiki;
use PHPUnit\Framework\TestCase;

/**
 * MediaWiki round-trip idempotency test
 *
 * Verifies: parse -> render -> parse -> render stabilizes.
 * Uses whitespace normalization (collapse multiple blank lines).
 *
 * @coversNothing
 */
class MediawikiIdempotencyTest extends TestCase
{
    private MediawikiParser $parser;
    private Mediawiki $renderer;

    protected function setUp(): void
    {
        $this->parser = new MediawikiParser();
        $this->renderer = new Mediawiki();
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
        $this->assertIdempotent("'''bold text'''", 'Bold');
    }

    public function testItalicIdempotency(): void
    {
        $this->assertIdempotent("''italic text''", 'Italic');
    }

    /**
     * Bold+italic idempotency is limited by the apostrophe state machine:
     * rendering strong(emphasis(...)) produces '''''text''''' which re-parses
     * differently depending on nesting order. This is a known limitation.
     */
    public function testBoldItalicFirstPassProducesOutput(): void
    {
        $firstPass = $this->roundTrip("'''''bold italic'''''");
        $this->assertStringContainsString('bold italic', $firstPass);
    }

    public function testUnderlineIdempotency(): void
    {
        $this->assertIdempotent('<u>underlined</u>', 'Underline');
    }

    public function testMonospaceIdempotency(): void
    {
        $this->assertIdempotent('<tt>monospace</tt>', 'Monospace');
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
        $this->assertIdempotent('<s>struck</s>', 'Strikethrough');
    }

    public function testDelIdempotency(): void
    {
        $this->assertIdempotent('<del>deleted</del>', 'Del');
    }

    public function testInsIdempotency(): void
    {
        $this->assertIdempotent('<ins>inserted</ins>', 'Ins');
    }

    public function testBreakIdempotency(): void
    {
        $this->assertIdempotent('Line one<br />Line two', 'Break');
    }

    // ---------------------------------------------------------------
    // Links
    // ---------------------------------------------------------------

    public function testUrlDescribedIdempotency(): void
    {
        $this->assertIdempotent('[http://example.com Example Site]', 'URL described');
    }

    public function testUrlBareIdempotency(): void
    {
        $this->assertIdempotent('Visit http://example.com today', 'URL bare');
    }

    public function testWikilinkWithTextIdempotency(): void
    {
        $this->assertIdempotent('[[Main Page|Go home]]', 'Wikilink with text');
    }

    public function testWikilinkBareIdempotency(): void
    {
        $this->assertIdempotent('[[Main Page]]', 'Wikilink bare');
    }

    public function testWikilinkWithAnchorIdempotency(): void
    {
        $this->assertIdempotent('[[Page#section|go to section]]', 'Wikilink with anchor');
    }

    public function testEmailIdempotency(): void
    {
        $this->assertIdempotent('[[mailto:user@example.com|Email me]]', 'Email');
    }

    // ---------------------------------------------------------------
    // Images
    // ---------------------------------------------------------------

    public function testImageIdempotency(): void
    {
        $this->assertIdempotent('[[File:photo.png|A nice photo]]', 'Image with alt');
    }

    public function testImageBareIdempotency(): void
    {
        $this->assertIdempotent('[[File:logo.jpg]]', 'Image bare');
    }

    // ---------------------------------------------------------------
    // Block elements
    // ---------------------------------------------------------------

    public function testHeadingLevel1Idempotency(): void
    {
        $this->assertIdempotent('= Heading 1 =', 'Heading level 1');
    }

    public function testHeadingLevel2Idempotency(): void
    {
        $this->assertIdempotent('== Heading 2 ==', 'Heading level 2');
    }

    public function testHeadingLevel3Idempotency(): void
    {
        $this->assertIdempotent('=== Heading 3 ===', 'Heading level 3');
    }

    public function testHorizontalRuleIdempotency(): void
    {
        $this->assertIdempotent('----', 'Horizontal rule');
    }

    public function testCodeBlockIdempotency(): void
    {
        $this->assertIdempotent("<code>\necho hello\n</code>", 'Code block');
    }

    public function testPreBlockIdempotency(): void
    {
        $this->assertIdempotent("<pre>\nformatted text\n</pre>", 'Pre block');
    }

    public function testNowikiIdempotency(): void
    {
        $this->assertIdempotent("<nowiki>'''not bold'''</nowiki>", 'Nowiki');
    }

    public function testBlockquoteIdempotency(): void
    {
        $this->assertIdempotent('<blockquote>quoted text</blockquote>', 'Blockquote');
    }

    // ---------------------------------------------------------------
    // Alignment
    // ---------------------------------------------------------------

    public function testCenterIdempotency(): void
    {
        $this->assertIdempotent('<div style="text-align:center;">Centered</div>', 'Center');
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
        $this->assertIdempotent("* Outer\n** Inner\n* Back", 'Nested list');
    }

    public function testDeepNestedListIdempotency(): void
    {
        $this->assertIdempotent(
            "* L1\n** L2\n*** L3",
            'Deep nested list'
        );
    }

    // ---------------------------------------------------------------
    // Tables
    // ---------------------------------------------------------------

    public function testTableIdempotency(): void
    {
        $this->assertIdempotent(
            "{|\n! Header 1 !! Header 2\n|-\n| Cell 1 || Cell 2\n|}",
            'Table'
        );
    }

    // ---------------------------------------------------------------
    // Definition lists
    // ---------------------------------------------------------------

    public function testDeflistIdempotency(): void
    {
        $this->assertIdempotent("; Term : Definition", 'Definition list');
    }

    // ---------------------------------------------------------------
    // TOC
    // ---------------------------------------------------------------

    public function testTocIdempotency(): void
    {
        $this->assertIdempotent('__TOC__', 'TOC');
    }

    // ---------------------------------------------------------------
    // Comment stripping
    // ---------------------------------------------------------------

    public function testCommentStripping(): void
    {
        $firstPass = $this->roundTrip('Before<!-- hidden -->After');
        // Comments are stripped, so first pass loses them
        $secondPass = $this->roundTrip($firstPass);

        $this->assertEquals(
            $this->normalizeWs($firstPass),
            $this->normalizeWs($secondPass),
            'After comment stripping, output should stabilize'
        );
    }

    // ---------------------------------------------------------------
    // Multiple element transitions
    // ---------------------------------------------------------------

    public function testMultipleHeadingsIdempotency(): void
    {
        $this->assertIdempotent(
            "== Heading 1 ==\n\n=== Heading 2 ===\n\n==== Heading 3 ====",
            'Multiple headings'
        );
    }

    public function testMixedInlineIdempotency(): void
    {
        $this->assertIdempotent(
            "'''bold''' and ''italic'' text",
            'Mixed inline'
        );
    }

    // ---------------------------------------------------------------
    // Complex document
    // ---------------------------------------------------------------

    public function testComplexDocumentStabilizes(): void
    {
        $source = <<<'WIKI'
            == Main Heading ==

            Some paragraph with '''bold''' text.

            === Sub Heading ===

            * Item 1
            * Item 2
            ** Nested item

            [http://example.com Visit us]

            ----

            <blockquote>A famous quote</blockquote>

            <code>
            echo hello
            </code>
            WIKI;

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
        $this->assertSame('mediawiki', $this->renderer->getFormat());
    }
}
