<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Test\Integration;

use Horde\Text\Wiki\Renderer\Yawiki;
use Horde\Text\Wiki\Yawiki\YawikiParser;
use PHPUnit\Framework\TestCase;

/**
 * Yawiki round-trip idempotency test
 *
 * Verifies: parse → render → parse → render stabilizes.
 * Uses whitespace normalization (collapse multiple blank lines).
 *
 * @coversNothing
 */
class YawikiIdempotencyTest extends TestCase
{
    private YawikiParser $parser;
    private Yawiki $renderer;

    protected function setUp(): void
    {
        $this->parser = new YawikiParser();
        $this->renderer = new Yawiki();
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
        $this->assertIdempotent("'''bold text'''", 'Bold');
    }

    public function testItalicIdempotency(): void
    {
        $this->assertIdempotent("''italic text''", 'Italic');
    }

    public function testStrongIdempotency(): void
    {
        $this->assertIdempotent('**strong text**', 'Strong');
    }

    public function testEmphasisIdempotency(): void
    {
        $this->assertIdempotent('//emphasis text//', 'Emphasis');
    }

    public function testUnderlineIdempotency(): void
    {
        $this->assertIdempotent('__underlined__', 'Underline');
    }

    public function testMonospaceIdempotency(): void
    {
        $this->assertIdempotent('{{monospace}}', 'Monospace');
    }

    public function testSuperscriptIdempotency(): void
    {
        $this->assertIdempotent('E=mc^^2^^', 'Superscript');
    }

    public function testSubscriptIdempotency(): void
    {
        $this->assertIdempotent('H,,2,,O', 'Subscript');
    }

    // ---------------------------------------------------------------
    // Links
    // ---------------------------------------------------------------

    public function testUrlWithTextIdempotency(): void
    {
        $this->assertIdempotent('[http://example.com Example Site]', 'URL with text');
    }

    public function testUrlBareIdempotency(): void
    {
        $this->assertIdempotent('[http://example.com]', 'Bare URL');
    }

    public function testFreelinkIdempotency(): void
    {
        $this->assertIdempotent('((Some Page))', 'Freelink');
    }

    public function testPhplookupIdempotency(): void
    {
        $this->assertIdempotent('[[php strlen]]', 'PHP lookup');
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
        $this->assertIdempotent('----', 'Horizontal rule');
    }

    public function testCodeBlockIdempotency(): void
    {
        $this->assertIdempotent("<code>\necho 'hello';\n</code>", 'Code block');
    }

    public function testBlockquoteIdempotency(): void
    {
        $this->assertIdempotent('> quoted text', 'Blockquote');
    }

    public function testCenterIdempotency(): void
    {
        $this->assertIdempotent('= centered text', 'Center');
    }

    // ---------------------------------------------------------------
    // Lists — nesting edge cases
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
            "|| ~ Header 1 || ~ Header 2 ||\n|| Cell 1 || Cell 2 ||",
            'Table'
        );
    }

    // ---------------------------------------------------------------
    // Miscellaneous
    // ---------------------------------------------------------------

    public function testColortextNamedIdempotency(): void
    {
        $this->assertIdempotent('##red|colored text##', 'Colortext named');
    }

    public function testColortextHexIdempotency(): void
    {
        $this->assertIdempotent('##FF0000|red text##', 'Colortext hex');
    }

    public function testAnchorIdempotency(): void
    {
        $this->assertIdempotent('[[# myanchor]]', 'Anchor');
    }

    public function testTocIdempotency(): void
    {
        $this->assertIdempotent('[[toc]]', 'TOC');
    }

    public function testImageIdempotency(): void
    {
        $this->assertIdempotent('[[image http://example.com/img.png]]', 'Image');
    }

    public function testDefinitionListIdempotency(): void
    {
        $this->assertIdempotent(': Term : Definition', 'Definition list');
    }

    // ---------------------------------------------------------------
    // Revision marks
    // ---------------------------------------------------------------

    public function testRevisePairedIdempotency(): void
    {
        $this->assertIdempotent('@@---old text+++new text@@', 'Paired revise');
    }

    public function testReviseDeleteOnlyIdempotency(): void
    {
        $this->assertIdempotent('@@---deleted text@@', 'Delete-only revise');
    }

    public function testReviseInsertOnlyIdempotency(): void
    {
        $this->assertIdempotent('@@+++inserted text@@', 'Insert-only revise');
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
            "Text before\n\n----\n\nText after",
            'Horiz with surrounding text'
        );
    }

    public function testCodeBlockWithSurroundingTextIdempotency(): void
    {
        $this->assertIdempotent(
            "Before\n\n<code>\necho 'hello';\n</code>\n\nAfter",
            'Code with surrounding text'
        );
    }

    public function testMultilineBlockquoteIdempotency(): void
    {
        $this->assertIdempotent(
            "> Line 1\n> Line 2\n> Line 3",
            'Multiline blockquote'
        );
    }

    public function testCenterFollowedByInlineIdempotency(): void
    {
        $this->assertIdempotent(
            "= centered\n\n@@---old+++new@@",
            'Center followed by revise'
        );
    }

    public function testHeadingFollowedByInlineIdempotency(): void
    {
        $this->assertIdempotent(
            "+ Heading\n\nSome **bold** text",
            'Heading followed by inline'
        );
    }

    public function testTableWithAlignmentIdempotency(): void
    {
        $this->assertIdempotent(
            "|| ~ Name || ~ Value ||\n|| > right || = center ||",
            'Table with alignment prefixes'
        );
    }

    public function testMultipleDefinitionsIdempotency(): void
    {
        $this->assertIdempotent(
            ": Term1 : Def1\n: Term2 : Def2",
            'Multiple definition list entries'
        );
    }

    // ---------------------------------------------------------------
    // Combined / complex documents
    // ---------------------------------------------------------------

    public function testMixedInlineIdempotency(): void
    {
        $this->assertIdempotent(
            '**bold text** and //italic text//',
            'Mixed inline'
        );
    }

    public function testComplexDocumentStabilizes(): void
    {
        $source = <<<'YAWIKI'
+ Main Heading

Some paragraph with **bold** and //italic// text.

++ Sub Heading

* Item 1
* Item 2
 * Nested item

[http://example.com Visit us]

----

> A famous quote

|| ~ Header 1 || ~ Header 2 ||
|| Cell A || Cell B ||

: Term : Definition

##red|colored text##

[[# myanchor]]

[[toc]]

[[image http://example.com/img.png]]

= centered text

@@---old text+++new text@@
YAWIKI;

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
        $this->assertSame('yawiki', $this->renderer->getFormat());
    }
}
