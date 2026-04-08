<?php

declare(strict_types=1);

/**
 * Copyright 2025-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Test\Integration;

use Horde\Text\Wiki\Commonmark\MarkdownParser;
use Horde\Text\Wiki\Renderer\Markdown;
use PHPUnit\Framework\TestCase;

/**
 * Markdown round-trip idempotency test
 *
 * Verifies: parse -> render -> parse -> render stabilizes.
 * Uses whitespace normalization (collapse multiple blank lines).
 *
 * @coversNothing
 */
class MarkdownIdempotencyTest extends TestCase
{
    private MarkdownParser $parser;
    private Markdown $renderer;

    protected function setUp(): void
    {
        $this->parser = new MarkdownParser();
        $this->renderer = new Markdown();
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
        $this->assertIdempotent('**bold text**', 'Bold');
    }

    public function testItalicIdempotency(): void
    {
        $this->assertIdempotent('*italic text*', 'Italic');
    }

    public function testStrikethroughIdempotency(): void
    {
        $this->assertIdempotent('~~struck text~~', 'Strikethrough');
    }

    public function testCodeSpanIdempotency(): void
    {
        $this->assertIdempotent('`code span`', 'Code span');
    }

    public function testCodeSpanWithBackticksIdempotency(): void
    {
        $this->assertIdempotent('``code with `backtick` inside``', 'Code span with backticks');
    }

    public function testNestedEmphasisIdempotency(): void
    {
        $this->assertIdempotent('**bold *and italic* text**', 'Nested emphasis');
    }

    // ---------------------------------------------------------------
    // Links and images
    // ---------------------------------------------------------------

    public function testLinkIdempotency(): void
    {
        $this->assertIdempotent('[Example](http://example.com)', 'Link');
    }

    public function testLinkWithTitleIdempotency(): void
    {
        $this->assertIdempotent('[Example](http://example.com "A Title")', 'Link with title');
    }

    public function testImageIdempotency(): void
    {
        $this->assertIdempotent('![alt text](http://example.com/img.png)', 'Image');
    }

    public function testImageWithTitleIdempotency(): void
    {
        $this->assertIdempotent('![alt](http://example.com/img.png "Title")', 'Image with title');
    }

    // ---------------------------------------------------------------
    // Headings
    // ---------------------------------------------------------------

    public function testHeadingLevel1Idempotency(): void
    {
        $this->assertIdempotent('# Heading 1', 'Heading level 1');
    }

    public function testHeadingLevel3Idempotency(): void
    {
        $this->assertIdempotent('### Heading 3', 'Heading level 3');
    }

    public function testHeadingLevel6Idempotency(): void
    {
        $this->assertIdempotent('###### Heading 6', 'Heading level 6');
    }

    // ---------------------------------------------------------------
    // Block elements
    // ---------------------------------------------------------------

    public function testThematicBreakIdempotency(): void
    {
        $this->assertIdempotent('---', 'Thematic break');
    }

    public function testFencedCodeIdempotency(): void
    {
        $this->assertIdempotent("```\necho 'hello';\n```", 'Fenced code');
    }

    public function testFencedCodeWithLanguageIdempotency(): void
    {
        $this->assertIdempotent("```php\necho 'hello';\n```", 'Fenced code with language');
    }

    public function testBlockquoteIdempotency(): void
    {
        $this->assertIdempotent('> quoted text', 'Blockquote');
    }

    public function testMultilineBlockquoteIdempotency(): void
    {
        $this->assertIdempotent(
            "> line 1\n> line 2\n> line 3",
            'Multiline blockquote'
        );
    }

    public function testNestedBlockquoteIdempotency(): void
    {
        $this->assertIdempotent(
            "> > nested blockquote",
            'Nested blockquote'
        );
    }

    // ---------------------------------------------------------------
    // Lists
    // ---------------------------------------------------------------

    public function testBulletListIdempotency(): void
    {
        $this->assertIdempotent("- Item 1\n- Item 2\n- Item 3", 'Bullet list');
    }

    public function testOrderedListIdempotency(): void
    {
        $this->assertIdempotent("1. First\n2. Second\n3. Third", 'Ordered list');
    }

    public function testNestedBulletListIdempotency(): void
    {
        $this->assertIdempotent(
            "- Outer\n  - Inner\n- Back",
            'Nested bullet list'
        );
    }

    public function testNestedMixedListIdempotency(): void
    {
        $this->assertIdempotent(
            "- Bullet\n  1. Ordered\n  2. Ordered 2\n- Back",
            'Nested mixed list'
        );
    }

    public function testTightListIdempotency(): void
    {
        $this->assertIdempotent("- a\n- b\n- c", 'Tight list');
    }

    public function testLooseListIdempotency(): void
    {
        $this->assertIdempotent("- a\n\n- b\n\n- c", 'Loose list');
    }

    // ---------------------------------------------------------------
    // Tables (GFM)
    // ---------------------------------------------------------------

    public function testSimpleTableIdempotency(): void
    {
        $this->assertIdempotent(
            "| A | B |\n| --- | --- |\n| 1 | 2 |",
            'Simple table'
        );
    }

    public function testTableWithAlignmentIdempotency(): void
    {
        $this->assertIdempotent(
            "| Left | Center | Right |\n| :--- | :---: | ---: |\n| a | b | c |",
            'Table with alignment'
        );
    }

    public function testTableWithEmptyCellsIdempotency(): void
    {
        $this->assertIdempotent(
            "| A | B |\n| --- | --- |\n|  |  |",
            'Table with empty cells'
        );
    }

    // ---------------------------------------------------------------
    // Breaks
    // ---------------------------------------------------------------

    public function testHardBreakIdempotency(): void
    {
        $this->assertIdempotent("hard break  \nhere", 'Hard break');
    }

    public function testSoftBreakIdempotency(): void
    {
        $this->assertIdempotent("soft\nbreak", 'Soft break');
    }

    // ---------------------------------------------------------------
    // HTML
    // ---------------------------------------------------------------

    public function testHtmlBlockIdempotency(): void
    {
        $this->assertIdempotent("<div>\nsome content\n</div>", 'HTML block');
    }

    public function testHtmlInlineIdempotency(): void
    {
        $this->assertIdempotent('text with <em>inline html</em>', 'HTML inline');
    }

    // ---------------------------------------------------------------
    // Escaping
    // ---------------------------------------------------------------

    public function testSpecialCharactersIdempotency(): void
    {
        $this->assertIdempotent(
            'Text with \\*asterisks\\* and \\[brackets\\]',
            'Special characters'
        );
    }

    // ---------------------------------------------------------------
    // Combined / complex documents
    // ---------------------------------------------------------------

    public function testComplexDocumentStabilizes(): void
    {
        $source = <<<'MD'
# Main Heading

Some paragraph with **bold** and *italic* text.

## Sub Heading

- Item 1
- Item 2
  - Nested item

[Visit us](http://example.com)

---

> A famous quote

| Header 1 | Header 2 |
| --- | --- |
| Cell A | Cell B |

```php
echo "hello";
```

~~strikethrough text~~

hard break
and more text
MD;

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

    // ---------------------------------------------------------------
    // Format
    // ---------------------------------------------------------------

    public function testGetFormat(): void
    {
        $this->assertSame('markdown', $this->renderer->getFormat());
    }
}
