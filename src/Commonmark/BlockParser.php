<?php

declare(strict_types=1);

/**
 * Copyright 2025-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Commonmark;

use Horde\Text\Wiki\Node\DocumentNode;
use Horde\Text\Wiki\Node\ElementNode;
use Horde\Text\Wiki\Node\TextNode;
use Horde\Text\Wiki\TagRegistry;

/**
 * CommonMark block parser — line-by-line block structure building
 *
 * Implements phase 1 of the CommonMark parsing algorithm:
 * each line is tested against open blocks (continuation), then
 * checked for new block starts.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class BlockParser
{
    private BlockContext $context;
    private bool $lastLineBlank = false;
    private bool $lastPhysicalLineBlank = false;
    private int $origColumnOffset = 0;

    public function __construct(TagRegistry $registry)
    {
        $this->context = new BlockContext($registry);
    }

    /**
     * Parse text into block structure
     *
     * @return BlockContext Context containing document and reference map
     */
    public function parse(string $text): BlockContext
    {
        $lines = $this->splitLines($text);

        foreach ($lines as $line) {
            $this->processLine($line);
        }

        // Close remaining open blocks
        $this->finalizeDocument();

        return $this->context;
    }

    /**
     * Split input into lines, handling \r\n, \r, and \n
     *
     * @return list<string>
     */
    private function splitLines(string $text): array
    {
        // Normalize line endings
        $text = str_replace("\r\n", "\n", $text);
        $text = str_replace("\r", "\n", $text);

        return explode("\n", $text);
    }

    private function processLine(string $line): void
    {
        // Store original line (with tabs) for content preservation in code blocks
        $originalLine = $line;

        // Replace tabs with spaces for indentation calculation
        $line = $this->expandTabs($line);

        // ------------------------------------------------------------------
        // Phase 1: Walk open container blocks, checking continuation.
        // Strip container prefixes ("> " for blockquotes, indentation for
        // list items) as we go. Track how far down the stack we matched.
        // ------------------------------------------------------------------
        $openBlocks = $this->context->getOpenBlocks();
        $allMatched = true;
        $lastMatchedIndex = -1; // index in openBlocks of last matched container
        $offset = 0; // character offset into $line after prefix stripping

        for ($i = 0; $i < count($openBlocks); $i++) {
            $block = $openBlocks[$i];
            if (!$block->isOpen()) {
                continue;
            }

            $containerType = $block->type;

            // Leaf blocks are handled in phase 2 — stop matching here
            if (!in_array($containerType, ['blockquote', 'list', 'listitem'], true)) {
                break;
            }

            if ($containerType === 'blockquote') {
                $rest = substr($line, $offset);
                $stripped = ltrim($rest);
                $bqIndent = strlen($rest) - strlen($stripped);

                if ($bqIndent < 4 && str_starts_with($stripped, '> ')) {
                    $offset += $bqIndent + 2; // skip indent + "> "
                    $lastMatchedIndex = $i;
                } elseif ($bqIndent < 4 && $stripped === '>') {
                    $offset = strlen($line); // blank inside blockquote
                    $lastMatchedIndex = $i;
                } elseif ($bqIndent < 4 && str_starts_with($stripped, '>')) {
                    $offset += $bqIndent + 1; // skip indent + ">"
                    $lastMatchedIndex = $i;
                } else {
                    $allMatched = false;
                    break;
                }
            } elseif ($containerType === 'listitem') {
                $padding = $block->node->getAttributes()['padding'] ?? 2;
                $rest = substr($line, $offset);

                // Check if this is a blank line
                if (trim($rest) === '') {
                    // Blank lines are OK inside list items
                    $lastMatchedIndex = $i;
                    continue;
                }

                // Count available spaces at current offset
                $availableIndent = 0;
                $j = $offset;
                while ($j < strlen($line) && $line[$j] === ' ') {
                    $availableIndent++;
                    $j++;
                }

                if ($availableIndent >= $padding) {
                    $offset += $padding;
                    $lastMatchedIndex = $i;
                } else {
                    $allMatched = false;
                    break;
                }
            } elseif ($containerType === 'list') {
                // A list continues only when its child listitem continues.
                // Since list items follow lists on the stack, the listitem
                // check below will handle this. We tentatively mark the list
                // as matched, and the listitem check may unmark it.
                $lastMatchedIndex = $i;
            }
        }

        // The remainder of the line after stripping container prefixes
        $remainder = substr($line, $offset);
        // For original line, do the same prefix stripping for code content
        [$origRemainder, $origColumnOffset] = $this->stripOriginalPrefixes($originalLine, $openBlocks, $lastMatchedIndex);
        $this->origColumnOffset = $origColumnOffset;

        // ------------------------------------------------------------------
        // Phase 2: Check lazy continuation BEFORE closing unmatched blocks
        // ------------------------------------------------------------------

        if (!$allMatched) {
            // Check for lazy continuation: if the tip is a paragraph and
            // the remainder doesn't start a block that interrupts paragraphs,
            // then lazily continue the paragraph.
            // Exception: if the line could be a new list item in the current list,
            // don't lazily continue — let it be processed as a new item.
            $tip = $this->context->getTip();
            if ($tip !== null && $tip->type === 'paragraph'
                && trim($remainder) !== ''
                && !$this->canInterruptParagraph($remainder)
                && !$this->couldBeListItemInOpenList($remainder)
            ) {
                // Lazy continuation — append to the open paragraph
                $tip->appendContent(ltrim($origRemainder));
                return;
            }

            // Not lazy continuation — close unmatched blocks
            $this->closeUnmatchedBlocks($lastMatchedIndex);
        }

        // Now process the remainder
        $this->processRemainder($remainder, $origRemainder, $allMatched, $lastMatchedIndex);

        // Track physical blank line status (set AFTER processing so it's
        // available during closeUnmatchedBlocks of the NEXT line)
        $this->lastPhysicalLineBlank = (trim($line) === '');
    }

    /**
     * Check if a line can interrupt a paragraph
     * (blocks that can start even when a paragraph is open)
     */
    private function canInterruptParagraph(string $line): bool
    {
        $trimmed = ltrim($line);
        $indent = strlen($line) - strlen($trimmed);

        if ($indent >= 4) {
            return false; // Indented code cannot interrupt paragraph
        }

        // Thematic break
        if (preg_match('/^(?:\*[ \t]*){3,}$/', $trimmed)
            || preg_match('/^(?:-[ \t]*){3,}$/', $trimmed)
            || preg_match('/^(?:_[ \t]*){3,}$/', $trimmed)
        ) {
            return true;
        }

        // ATX heading
        if (preg_match('/^#{1,6}(?:\s|$)/', $trimmed)) {
            return true;
        }

        // Fenced code
        if (preg_match('/^(`{3,})[^`]*$/', $trimmed) || preg_match('/^~{3,}/', $trimmed)) {
            return true;
        }

        // HTML block types 1-6 (type 7 cannot interrupt paragraph)
        $htmlBlockType = $this->detectHtmlBlockType($trimmed);
        if ($htmlBlockType >= 1 && $htmlBlockType <= 6) {
            return true;
        }

        // Block quote
        if (str_starts_with($trimmed, '> ') || $trimmed === '>') {
            return true;
        }

        // List items (bullet or ordered with start=1)
        // Empty list items cannot interrupt paragraphs
        if (preg_match('/^[*+-]\s/', $trimmed)) {
            return true;
        }
        if (preg_match('/^(\d{1,9})([.)])\s/', $trimmed, $m)) {
            // Only ordered lists starting with 1 can interrupt paragraph
            if ((int) $m[1] === 1) {
                return true;
            }
        }

        // Setext heading underlines can interrupt — but they're handled separately
        // as paragraph continuation/conversion

        return false;
    }

    /**
     * Check if a line could be a list item belonging to an open list
     * (regardless of whether it can interrupt a paragraph).
     * Also returns true for different-type markers since they close
     * the current list and start a new one.
     */
    private function couldBeListItemInOpenList(string $line): bool
    {
        $trimmed = ltrim($line);
        $indent = strlen($line) - strlen($trimmed);
        if ($indent >= 4) {
            return false;
        }

        // Find if there's an open list in the stack
        $hasOpenList = false;
        foreach ($this->context->getOpenBlocks() as $block) {
            if ($block->type === 'list' && $block->isOpen()) {
                $hasOpenList = true;
            }
        }
        if (!$hasOpenList) {
            return false;
        }

        // Check if this line starts ANY list item marker
        if (preg_match('/^[*+-](\s{1,4}|\s*$)/', $trimmed)) {
            return true;
        }
        if (preg_match('/^(\d{1,9})([.)])(\s{1,4}|\s*$)/', $trimmed)) {
            return true;
        }

        return false;
    }

    /**
     * Strip container prefixes from the original (unexpanded) line
     * to preserve tab characters in code block content.
     *
     * Returns [remaining_string, column_offset] where column_offset
     * is the virtual column position of the first character in the
     * remaining string (needed for correct tab-stop computation).
     *
     * @return array{string, int}
     */
    private function stripOriginalPrefixes(string $line, array $openBlocks, int $lastMatchedIndex): array
    {
        $offset = 0;    // byte offset into the line
        $column = 0;    // virtual column position (tab-aware)

        for ($i = 0; $i <= $lastMatchedIndex && $i < count($openBlocks); $i++) {
            $block = $openBlocks[$i];
            if (!$block->isOpen()) {
                continue;
            }

            if ($block->type === 'blockquote') {
                // Skip whitespace + > + optional space
                $rest = substr($line, $offset);
                $stripped = ltrim($rest);
                $bqIndent = strlen($rest) - strlen($stripped);

                if (str_starts_with($stripped, '> ')) {
                    $offset += $bqIndent + 2;
                    $column = 0; // reset column after blockquote marker
                } elseif ($stripped === '>' || $stripped === '') {
                    $offset = strlen($line);
                    $column = 0;
                } elseif (str_starts_with($stripped, '>')) {
                    $offset += $bqIndent + 1;
                    $column = 0;
                }
            } elseif ($block->type === 'listitem') {
                $padding = $block->node->getAttributes()['padding'] ?? 2;
                $removed = 0;
                while ($removed < $padding && $offset < strlen($line)) {
                    if ($line[$offset] === "\t") {
                        $tabWidth = 4 - ($column % 4);
                        if ($removed + $tabWidth <= $padding) {
                            $removed += $tabWidth;
                            $column += $tabWidth;
                            $offset++;
                        } else {
                            // Partial tab consumption: the tab is consumed but
                            // column advances only by the consumed portion.
                            // The remaining columns become the start of the content.
                            $consumed = $padding - $removed;
                            $column += $consumed;
                            $removed += $consumed;
                            $offset++;
                            // The content starts at this column offset, with
                            // remaining (tabWidth - consumed) virtual spaces
                            // before the next byte
                            $remainingSpaces = $tabWidth - $consumed;
                            return [str_repeat(' ', $remainingSpaces) . substr($line, $offset), $column];
                        }
                    } elseif ($line[$offset] === ' ') {
                        $removed++;
                        $column++;
                        $offset++;
                    } else {
                        break;
                    }
                }
            }
        }

        return [substr($line, $offset), $column];
    }

    /**
     * Close all open blocks deeper than the given matched index
     */
    private function closeUnmatchedBlocks(int $lastMatchedIndex): void
    {
        $openBlocks = $this->context->getOpenBlocks();
        // Close from tip back to lastMatchedIndex + 1
        while (count($this->context->getOpenBlocks()) > $lastMatchedIndex + 1) {
            $tip = $this->context->getTip();
            if ($tip === null) {
                break;
            }
            $this->finalizeBlock($tip);
        }
    }

    /**
     * Finalize any block type
     */
    private function finalizeBlock(OpenBlock $block): void
    {
        match ($block->type) {
            'paragraph' => $this->finalizeParagraph($block),
            'fenced_code' => $this->closeFencedCode($block),
            'indented_code' => $this->finalizeIndentedCode($block),
            'html_block' => $this->finalizeHtmlBlock($block),
            'listitem' => $this->closeListItem($block),
            'list' => $this->closeList($block),
            'blockquote' => $this->closeBlockquote($block),
            'table' => (function () use ($block) {
                $block->close();
                $this->context->closeLastBlock();
            })(),
            default => (function () use ($block) {
                $block->close();
                $this->context->closeLastBlock();
            })(),
        };
    }

    /**
     * Close a blockquote — finalize everything inside it first
     */
    private function closeBlockquote(OpenBlock $block): void
    {
        $block->close();
        $this->context->closeLastBlock();
    }

    /**
     * Check if a line could be a list item matching the given list's marker type
     */
    private function isMatchingListItem(string $line, OpenBlock $listBlock): bool
    {
        $trimmed = ltrim($line);
        $indent = strlen($line) - strlen($trimmed);
        if ($indent >= 4) {
            return false;
        }

        $listMarker = $listBlock->node->getAttributes()['marker'] ?? '';
        $listType = $listBlock->node->getAttributes()['type'] ?? '';

        if ($listType === 'bullet') {
            if (preg_match('/^([*+-])(\s{1,4}|\s*$)/', $trimmed, $m)) {
                return $m[1] === $listMarker;
            }
        } elseif ($listType === 'ordered') {
            if (preg_match('/^(\d{1,9})([.)])(\s{1,4}|\s*$)/', $trimmed, $m)) {
                return $m[2] === $listMarker;
            }
        }

        return false;
    }

    /**
     * Process the remainder of a line after container prefix stripping
     */
    private function processRemainder(string $line, string $originalLine, bool $allMatched, int $lastMatchedIndex): void
    {
        $tip = $this->context->getTip();
        $trimmed = ltrim($line);
        $indent = strlen($line) - strlen($trimmed);
        $isBlankLine = trim($line) === '';

        // If we're in a fenced code block, handle continuation/closing
        if ($tip !== null && $tip->type === 'fenced_code') {
            if (!$isBlankLine && $this->isFencedCodeCloser($line, $tip)) {
                $this->closeFencedCode($tip);
            } else {
                $tip->appendContent($this->removeFenceIndent($originalLine, $tip));
            }
            return;
        }

        // If we're in an HTML block, handle continuation/closing
        if ($tip !== null && $tip->type === 'html_block') {
            if ($isBlankLine) {
                $htmlType = $tip->node->getAttributes()['html_block_type'] ?? 0;
                if ($htmlType === 6 || $htmlType === 7) {
                    $this->finalizeHtmlBlock($tip);
                    return;
                }
            }
            $tip->appendContent($line);
            if ($this->isHtmlBlockCloser($line, $tip)) {
                $this->finalizeHtmlBlock($tip);
            }
            return;
        }

        // If we're in an indented code block
        if ($tip !== null && $tip->type === 'indented_code') {
            if ($indent >= 4) {
                $tip->appendContent($this->removeIndent($originalLine, 4, $this->origColumnOffset));
                return;
            } elseif ($isBlankLine) {
                $tip->appendContent('');
                return;
            } else {
                $this->finalizeIndentedCode($tip);
                $tip = $this->context->getTip();
                // Fall through to try other block starts
            }
        }

        // Handle blank lines
        if ($isBlankLine) {
            $this->lastLineBlank = true;
            $this->handleBlankLine();
            return;
        }

        $this->lastLineBlank = false;

        // If the tip is a list and the line doesn't continue it as a list item,
        // close the list before trying other block starts.
        // Also close the list if the line is a thematic break (even if syntactically
        // it could be a list item, like "* * *" in a *-list).
        $tip = $this->context->getTip();
        if ($tip !== null && $tip->type === 'list') {
            $isThematicBreak = $this->isThematicBreak($line);
            if ($isThematicBreak || !$this->isMatchingListItem($line, $tip)) {
                $this->closeList($tip);
                $tip = $this->context->getTip();
            }
        }

        // If the tip is a GFM table and the line starts a block-level construct
        // (not a table row), close the table so the line is processed normally.
        if ($tip !== null && $tip->type === 'table') {
            if ($this->isBlockStartLine($line)) {
                $tip->close();
                $this->context->closeLastBlock();
                $tip = $this->context->getTip();
            }
        }

        // Setext heading takes priority over thematic break when paragraph is open
        if ($tip !== null && $tip->type === 'paragraph' && $this->isSetextUnderline($line)) {
            $this->convertToSetextHeading($tip, $line);
            return;
        }

        // Try to start new blocks (in priority order per spec)
        if ($this->tryThematicBreak($line)) {
            return;
        }
        if ($this->tryAtxHeading($line)) {
            return;
        }
        if ($this->tryFencedCode($line)) {
            return;
        }
        if ($this->tryHtmlBlock($line)) {
            return;
        }
        if ($this->tryBlockquote($line)) {
            return;
        }
        if ($this->tryListItem($line)) {
            return;
        }
        if ($this->tryIndentedCode($line, $indent, $originalLine)) {
            return;
        }

        // Check for GFM table
        if ($this->context->hasGfm() && $tip !== null && $tip->type === 'paragraph') {
            if ($this->isTableDelimiterRow($line)) {
                if ($this->convertToTable($tip, $line)) {
                    return;
                }
            }
        }

        // Default: paragraph continuation or new paragraph
        $this->handleParagraph($line, $originalLine);
    }

    /**
     * Expand tabs to spaces with tab stops at multiples of 4
     */
    private function expandTabs(string $line): string
    {
        if (strpos($line, "\t") === false) {
            return $line;
        }

        $result = '';
        $col = 0;
        for ($i = 0; $i < strlen($line); $i++) {
            if ($line[$i] === "\t") {
                $spaces = 4 - ($col % 4);
                $result .= str_repeat(' ', $spaces);
                $col += $spaces;
            } else {
                $result .= $line[$i];
                $col++;
            }
        }
        return $result;
    }

    // tryContinuations, canContinue, canContinueBlockquote removed —
    // continuation logic is now in processLine phase 1

    // ---------------------------------------------------------------
    // Thematic break
    // ---------------------------------------------------------------

    private function tryThematicBreak(string $line): bool
    {
        if (!$this->isThematicBreak($line)) {
            return false;
        }
        $this->closeParagraphIfOpen();
        $node = new ElementNode('horiz');
        $this->addToDocument($node);
        return true;
    }

    private function isThematicBreak(string $line): bool
    {
        $trimmed = ltrim($line);
        if (strlen($line) - strlen($trimmed) >= 4) {
            return false;
        }

        return (bool) (preg_match('/^(?:\*[ \t]*){3,}$/', $trimmed)
            || preg_match('/^(?:-[ \t]*){3,}$/', $trimmed)
            || preg_match('/^(?:_[ \t]*){3,}$/', $trimmed));
    }

    // ---------------------------------------------------------------
    // ATX Heading
    // ---------------------------------------------------------------

    private function tryAtxHeading(string $line): bool
    {
        $trimmed = ltrim($line);
        $indent = strlen($line) - strlen($trimmed);
        if ($indent >= 4) {
            return false;
        }

        if (preg_match('/^(#{1,6})(?:\s|$)(.*)$/', $trimmed, $matches)) {
            $this->closeParagraphIfOpen();

            $level = strlen($matches[1]);
            $content = $matches[2];

            // Remove optional closing #s
            $content = preg_replace('/(?:^|\\s)#+\\s*$/', '', $content) ?? $content;
            $content = trim($content);

            $node = new ElementNode('heading', ['level' => $level]);
            // Content will be inline-parsed later
            if ($content !== '') {
                $node->addChild(new TextNode($content));
            }
            $this->addToDocument($node);
            return true;
        }

        return false;
    }

    // ---------------------------------------------------------------
    // Setext heading
    // ---------------------------------------------------------------

    private function isSetextUnderline(string $line): bool
    {
        $trimmed = ltrim($line);
        $indent = strlen($line) - strlen($trimmed);
        if ($indent >= 4) {
            return false;
        }

        return (bool) preg_match('/^(?:={1,}|-{1,})\s*$/', $trimmed);
    }

    private function convertToSetextHeading(OpenBlock $paragraph, string $underline): void
    {
        $trimmed = ltrim($underline);
        $level = ($trimmed[0] === '=') ? 1 : 2;

        $content = $paragraph->getContent();

        // Extract link reference definitions from the paragraph content first
        $content = $this->extractLinkReferences($content);
        $content = trim($content);

        if ($content === '') {
            // After extracting link refs, nothing remains.
            // The underline is not a setext heading — treat as paragraph.
            $this->removeLastBlock();
            $this->handleParagraph($underline);
            return;
        }

        // Remove paragraph from document
        $this->removeLastBlock();

        $node = new ElementNode('heading', ['level' => $level]);
        if ($content !== '') {
            $node->addChild(new TextNode($content));
        }
        $this->addToDocument($node);
    }

    // ---------------------------------------------------------------
    // Fenced code
    // ---------------------------------------------------------------

    private function tryFencedCode(string $line): bool
    {
        $trimmed = ltrim($line);
        $indent = strlen($line) - strlen($trimmed);
        if ($indent >= 4) {
            return false;
        }

        if (preg_match('/^(`{3,})([^`]*)$/', $trimmed, $matches)) {
            // Backtick fence — info string cannot contain backticks
            $this->closeParagraphIfOpen();
            return $this->openFencedCode($matches[1], $matches[2], $indent);
        }

        if (preg_match('/^(~{3,})(.*)$/', $trimmed, $matches)) {
            // Tilde fence — info string can contain anything
            $this->closeParagraphIfOpen();
            return $this->openFencedCode($matches[1], $matches[2], $indent);
        }

        return false;
    }

    private function openFencedCode(string $fenceChars, string $infoString, int $indent): bool
    {
        $fenceChar = $fenceChars[0];
        $fenceLength = strlen($fenceChars);
        $infoString = trim($infoString);

        // Extract language from info string (first word)
        $language = '';
        if ($infoString !== '') {
            $parts = preg_split('/\s+/', $infoString, 2);
            $language = $this->unescapeString($this->decodeHtmlEntities($parts[0]));
        }

        $node = new ElementNode('code', array_filter([
            'language' => $language,
            'fence_char' => $fenceChar,
            'fence_length' => $fenceLength,
            'fence_indent' => $indent,
        ]));
        $node->setVerbatim(true);

        $parent = $this->getCurrentParent();
        $parent->addChild($node);

        $block = new OpenBlock('fenced_code', $node, $parent);
        $this->context->openBlock($block);

        return true;
    }

    private function isFencedCodeCloser(string $line, OpenBlock $block): bool
    {
        $trimmed = ltrim($line);
        $indent = strlen($line) - strlen($trimmed);
        if ($indent >= 4) {
            return false;
        }

        $attrs = $block->node->getAttributes();
        $fenceChar = $attrs['fence_char'] ?? '`';
        $fenceLength = $attrs['fence_length'] ?? 3;

        $pattern = '/^' . preg_quote($fenceChar, '/') . '{' . $fenceLength . ',}\s*$/';
        return (bool) preg_match($pattern, $trimmed);
    }

    private function closeFencedCode(OpenBlock $block): void
    {
        $content = $block->getContent();
        // If there were any content lines, add trailing newline per spec
        if ($content !== '') {
            $content .= "\n";
        }
        if ($content !== '') {
            $block->node->addChild(new TextNode($content));
        }

        $block->close();
        $this->context->closeLastBlock();
    }

    private function removeFenceIndent(string $line, OpenBlock $block): string
    {
        $attrs = $block->node->getAttributes();
        $indent = $attrs['fence_indent'] ?? 0;
        if ($indent > 0 && strlen($line) > 0) {
            // Remove up to $indent spaces
            $removed = 0;
            $start = 0;
            while ($removed < $indent && $start < strlen($line) && $line[$start] === ' ') {
                $removed++;
                $start++;
            }
            return substr($line, $start);
        }
        return $line;
    }

    // ---------------------------------------------------------------
    // Indented code
    // ---------------------------------------------------------------

    private function tryIndentedCode(string $line, int $indent, string $originalLine = ''): bool
    {
        if ($indent < 4) {
            return false;
        }

        // Cannot start indented code in a paragraph
        $tip = $this->context->getTip();
        if ($tip !== null && $tip->type === 'paragraph') {
            return false;
        }

        $this->closeParagraphIfOpen();

        $node = new ElementNode('code');
        $node->setVerbatim(true);

        $parent = $this->getCurrentParent();
        $parent->addChild($node);

        $block = new OpenBlock('indented_code', $node, $parent);
        $contentLine = $originalLine !== '' ? $originalLine : $line;
        $block->appendContent($this->removeIndent($contentLine, 4, $this->origColumnOffset));
        $this->context->openBlock($block);

        return true;
    }

    private function finalizeIndentedCode(OpenBlock $block): void
    {
        $content = $block->getContent();
        // Remove trailing blank lines
        $content = preg_replace('/(\n\s*)+$/', '', $content) ?? $content;

        if ($content !== '') {
            $block->node->addChild(new TextNode($content . "\n"));
        }

        $block->close();
        $this->context->closeLastBlock();
    }

    // ---------------------------------------------------------------
    // HTML block
    // ---------------------------------------------------------------

    private function tryHtmlBlock(string $line): bool
    {
        $trimmed = ltrim($line);
        $indent = strlen($line) - strlen($trimmed);
        if ($indent >= 4) {
            return false;
        }

        $htmlBlockType = $this->detectHtmlBlockType($trimmed);
        if ($htmlBlockType === 0) {
            return false;
        }

        // Type 7 cannot interrupt a paragraph
        $tip = $this->context->getTip();
        if ($htmlBlockType === 7 && $tip !== null && $tip->type === 'paragraph') {
            return false;
        }

        $this->closeParagraphIfOpen();

        $attrs = ['html_block_type' => $htmlBlockType];
        $node = new ElementNode('htmlblock', $attrs);
        $node->setVerbatim(true);

        $parent = $this->getCurrentParent();
        $parent->addChild($node);

        $block = new OpenBlock('html_block', $node, $parent);
        $block->appendContent($line);
        $block->node->setAttribute('html_block_type', $htmlBlockType);
        $this->context->openBlock($block);

        // Check if this line also closes the block
        if ($this->isHtmlBlockCloser($line, $block)) {
            $this->finalizeHtmlBlock($block);
        }

        return true;
    }

    private function detectHtmlBlockType(string $line): int
    {
        // Type 1: <pre, <script, <style, <textarea
        if (preg_match('/^<(?:script|pre|style|textarea)(?:\s|>|$)/i', $line)) {
            return 1;
        }

        // Type 2: <!-- comment
        if (str_starts_with($line, '<!--')) {
            return 2;
        }

        // Type 3: <?
        if (str_starts_with($line, '<?')) {
            return 3;
        }

        // Type 4: <!LETTER
        if (preg_match('/^<![A-Z]/', $line)) {
            return 4;
        }

        // Type 5: <![CDATA[
        if (str_starts_with($line, '<![CDATA[')) {
            return 5;
        }

        // Type 6: starts with specific block-level tags
        $blockTags = 'address|article|aside|base|basefont|blockquote|body|caption|center|col|colgroup|dd|details|dialog|dir|div|dl|dt|fieldset|figcaption|figure|footer|form|frame|frameset|h1|h2|h3|h4|h5|h6|head|header|hr|html|iframe|legend|li|link|main|menu|menuitem|nav|noframes|ol|optgroup|option|p|param|search|section|summary|table|tbody|td|tfoot|th|thead|title|tr|track|ul';
        if (preg_match('/^<\/?(?:' . $blockTags . ')(?:\s|\/?>|$)/i', $line)) {
            return 6;
        }

        // Type 7: starts with an open tag or closing tag (not in type 6)
        if (preg_match('/^(?:<[a-zA-Z][a-zA-Z0-9-]*(?:\s+[a-zA-Z_:][a-zA-Z0-9_.:-]*(?:\s*=\s*(?:[^\s"\'=<>`]+|\'[^\']*\'|"[^"]*"))?)*\s*\/?>|<\/[a-zA-Z][a-zA-Z0-9-]*\s*>)\s*$/', $line)) {
            return 7;
        }

        return 0;
    }

    private function isHtmlBlockCloser(string $line, OpenBlock $block): bool
    {
        $type = $block->node->getAttributes()['html_block_type'] ?? 0;

        return match ($type) {
            1 => (bool) preg_match('/<\/(?:script|pre|style|textarea)>/i', $line),
            2 => str_contains($line, '-->'),
            3 => str_contains($line, '?>'),
            4 => str_contains($line, '>'),
            5 => str_contains($line, ']]>'),
            6, 7 => trim($line) === '',
            default => false,
        };
    }

    private function finalizeHtmlBlock(OpenBlock $block): void
    {
        $content = $block->getContent();
        if ($content !== '') {
            $block->node->addChild(new TextNode($content));
        }
        $block->close();
        $this->context->closeLastBlock();
    }

    // ---------------------------------------------------------------
    // Blockquote
    // ---------------------------------------------------------------

    private function tryBlockquote(string $line): bool
    {
        $trimmed = ltrim($line);
        $indent = strlen($line) - strlen($trimmed);
        if ($indent >= 4) {
            return false;
        }

        if (str_starts_with($trimmed, '> ') || $trimmed === '>' || str_starts_with($trimmed, '>')) {
            // Only match '>' at start (with optional space or no space before content)
            if (!str_starts_with($trimmed, '>')) {
                return false;
            }

            $this->closeParagraphIfOpen();

            $node = new ElementNode('blockquote');
            $parent = $this->getCurrentParent();
            $parent->addChild($node);

            $block = new OpenBlock('blockquote', $node, $parent);
            $this->context->openBlock($block);

            // Determine the rest of the line after the blockquote marker
            if (str_starts_with($trimmed, '> ')) {
                $rest = substr($trimmed, 2);
            } elseif ($trimmed === '>') {
                $rest = '';
            } else {
                // >text without space
                $rest = substr($trimmed, 1);
            }

            // Re-process the inner content through processRemainder
            // (not processLine, to avoid re-matching containers)
            if ($rest !== '' || $trimmed === '>') {
                $this->processRemainder($rest, $rest, true, count($this->context->getOpenBlocks()) - 1);
            }

            return true;
        }

        return false;
    }

    // ---------------------------------------------------------------
    // List
    // ---------------------------------------------------------------

    private function tryListItem(string $line): bool
    {
        $trimmed = ltrim($line);
        $indent = strlen($line) - strlen($trimmed);
        if ($indent >= 4) {
            return false;
        }

        // Bullet list: -, *, +
        // Note: regex allows 1-4 spaces after marker (standard) or end of line (empty).
        // For 5+ spaces, we need a separate match to handle code-in-item.
        if (preg_match('/^([*+-])(\s+)(.+)$/', $trimmed, $matches)
            || preg_match('/^([*+-])(\s{1,4})$/', $trimmed, $matches)
            || preg_match('/^([*+-])()$/', $trimmed, $matches)
        ) {
            $marker = $matches[1];
            $content = $matches[3] ?? '';
            $spacesAfterMarker = $matches[2];

            // Empty list items cannot interrupt paragraphs
            $tip = $this->context->getTip();
            if (trim($content) === '' && $tip !== null && $tip->type === 'paragraph') {
                return false;
            }

            // Per spec: if first line content is blank, padding = marker width + 1
            if (trim($content) === '' && trim($spacesAfterMarker) === '') {
                $padding = $indent + 2; // marker (1 char) + 1
            } elseif (strlen($spacesAfterMarker) > 4) {
                // Content starts with indented code (5+ spaces after marker).
                // Per spec section 5.2 rule 2: padding = marker width + 1
                $padding = $indent + 2;
                $content = substr($spacesAfterMarker, 1) . $content;
            } else {
                $padding = $indent + 1 + strlen($spacesAfterMarker);
            }

            return $this->startListItem('bullet', $marker, 0, $content, $padding);
        }

        // Ordered list: 1. or 1)
        // Note: The regex allows up to 4 spaces after marker, OR matches when
        // 5+ spaces are present as part of the marker-then-code pattern
        if (preg_match('/^(\d{1,9})([.)])(\s+)(.*)$/', $trimmed, $matches)
            || preg_match('/^(\d{1,9})([.)])(\s*)$/', $trimmed, $matches)
        ) {
            $start = (int) $matches[1];
            $marker = $matches[2];
            $content = $matches[4] ?? '';
            $spacesAfterMarker = $matches[3];
            $markerWidth = strlen($matches[1]) + 1; // digits + period/paren

            // Empty list items cannot interrupt paragraphs
            // Ordered list starting with number != 1 cannot interrupt paragraph
            $tip = $this->context->getTip();
            if ($tip !== null && $tip->type === 'paragraph') {
                if ($start !== 1 || trim($content) === '') {
                    return false;
                }
            }

            // Per spec: if first line content is blank, padding = marker width + 1
            if (trim($content) === '' && trim($spacesAfterMarker) === '') {
                $padding = $indent + $markerWidth + 1;
            } elseif (strlen($spacesAfterMarker) > 4) {
                // Content starts with indented code (5+ spaces after marker).
                // Per spec section 5.2 rule 2: padding = marker width + 1
                $padding = $indent + $markerWidth + 1;
                // The content includes the extra spaces that form the code indent
                $content = substr($spacesAfterMarker, 1) . $content;
            } else {
                $padding = $indent + $markerWidth + strlen($spacesAfterMarker);
            }

            return $this->startListItem('ordered', $marker, $start, $content, $padding);
        }

        return false;
    }

    private function startListItem(string $listType, string $marker, int $start, string $content, int $padding): bool
    {
        $this->closeParagraphIfOpen();

        // Check if we need to start a new list or continue existing
        $tip = $this->context->getTip();
        $listNode = null;

        if ($tip !== null && $tip->type === 'listitem') {
            // The tip is a list item — this means we're processing content
            // INSIDE this list item (first-line content or continuation content).
            // Create a sub-list inside the list item, don't close it.
            // (Sibling items are handled when phase 1 closes the old listitem
            //  before we reach startListItem.)
        } elseif ($tip !== null && $tip->type === 'list') {
            $listNode = $tip->node;
            $existingType = $listNode->getAttributes()['type'] ?? '';
            $existingMarker = $listNode->getAttributes()['marker'] ?? '';
            // Check if same list type AND marker character
            // Bullet: -, *, + each start separate lists
            // Ordered: . and ) each start separate lists
            if ($existingType !== $listType || $existingMarker !== $marker) {
                // Different list type or marker, close old list and start new
                $this->closeList($tip);
                $listNode = null;
            }
        }

        if ($listNode === null) {
            $attrs = ['type' => $listType, 'start' => $start, 'marker' => $marker];
            $listNode = new ElementNode('list', $attrs);
            $parent = $this->getCurrentParent();
            $parent->addChild($listNode);

            $listBlock = new OpenBlock('list', $listNode, $parent);
            $this->context->openBlock($listBlock);
        }

        // Create the list item
        $itemNode = new ElementNode('listitem');
        $listNode->addChild($itemNode);

        $itemBlock = new OpenBlock('listitem', $itemNode, $listNode);
        $itemBlock->node->setAttribute('padding', $padding);
        $this->context->openBlock($itemBlock);

        // Process the first line's content through processRemainder so that
        // block-level constructs (sub-lists, blockquotes, headings, code fences)
        // inside list items are properly detected
        if (trim($content) !== '') {
            $this->processRemainder($content, $content, true, count($this->context->getOpenBlocks()) - 1);
        }

        return true;
    }

    private function closeListItem(OpenBlock $block): void
    {
        // Finalize paragraph if open inside list item
        $tip = $this->context->getTip();
        if ($tip !== null && $tip->type === 'paragraph' && $tip->parent === $block->node) {
            $this->finalizeParagraph($tip);
        }
        // Track if this item ends with a blank line
        // Use lastPhysicalLineBlank (not lastLineBlank) to distinguish
        // physical blank lines from content-blank inside containers (e.g., "> ")
        if ($this->lastPhysicalLineBlank) {
            $block->node->setAttribute('ends_with_blank', true);
        }
        $block->close();
        $this->context->closeLastBlock();
    }

    private function closeList(OpenBlock $block): void
    {
        // Close any open list items first
        $tip = $this->context->getTip();
        while ($tip !== null && $tip !== $block) {
            if ($tip->type === 'listitem') {
                $this->closeListItem($tip);
            } elseif ($tip->type === 'paragraph') {
                $this->finalizeParagraph($tip);
            } else {
                $tip->close();
                $this->context->closeLastBlock();
            }
            $tip = $this->context->getTip();
        }

        // Determine tight/loose per CommonMark spec:
        // A list is loose if any of its constituent list items are separated
        // by blank lines, or if any constituent list item directly contains
        // two block-level elements with a blank line between them.
        $loose = false;
        $items = $block->node->getChildren();
        $itemCount = count($items);
        for ($i = 0; $i < $itemCount; $i++) {
            if (!($items[$i] instanceof ElementNode)) {
                continue;
            }

            // Check 1: non-last item that ends with a blank line
            if ($i < $itemCount - 1) {
                $endsBlank = $items[$i]->getAttributes()['ends_with_blank'] ?? false;
                $hadBlank = $items[$i]->getAttributes()['had_blank_line'] ?? false;
                $blankInNested = $items[$i]->getAttributes()['blank_in_nested'] ?? false;
                if ($endsBlank || ($hadBlank && !$blankInNested)) {
                    $loose = true;
                    break;
                }
            }

            $hadBlank = $items[$i]->getAttributes()['had_blank_line'] ?? false;
            if (!$hadBlank) {
                continue;
            }

            // Check 2: blank line between direct children of this item
            $blankInNested = $items[$i]->getAttributes()['blank_in_nested'] ?? false;
            $childrenAtBlank = $items[$i]->getAttributes()['children_at_blank'] ?? 0;
            $finalChildCount = count($items[$i]->getChildren());

            if ($blankInNested) {
                // Blank was inside a nested list. Counts for looseness ONLY if
                // the item gained new direct children after the blank
                if ($finalChildCount > $childrenAtBlank && $finalChildCount > 1) {
                    $loose = true;
                    break;
                }
                continue;
            }

            // Direct blank: item has multiple children → loose
            if ($finalChildCount > 1) {
                $loose = true;
                break;
            }
        }

        // Set tight attribute on all list items
        $tight = !$loose;
        foreach ($block->node->getChildren() as $item) {
            if ($item instanceof ElementNode) {
                $item->setAttribute('tight', $tight);
            }
        }

        $block->close();
        $this->context->closeLastBlock();
    }

    // ---------------------------------------------------------------
    // GFM Table
    // ---------------------------------------------------------------

    private function isTableDelimiterRow(string $line): bool
    {
        $trimmed = ltrim($line);
        $indent = strlen($line) - strlen($trimmed);
        if ($indent >= 4) {
            return false;
        }

        $trimmed = trim($line);
        if ($trimmed === '') {
            return false;
        }

        // Must contain only |, -, :, and whitespace
        if (!preg_match('/^[|:\-\s]+$/', $trimmed)) {
            return false;
        }

        // Split by | and check each cell is valid delimiter
        $cells = $this->splitTableRow($trimmed);
        if (empty($cells)) {
            return false;
        }

        foreach ($cells as $cell) {
            $cell = trim($cell);
            if (!preg_match('/^:?-+:?$/', $cell)) {
                return false;
            }
        }

        return true;
    }

    private function convertToTable(OpenBlock $paragraph, string $delimiterRow): bool
    {
        $headerLine = trim($paragraph->getContent());

        // Parse delimiter row for alignments
        $delimCells = $this->splitTableRow(trim($delimiterRow));
        $alignments = [];
        foreach ($delimCells as $cell) {
            $cell = trim($cell);
            $left = str_starts_with($cell, ':');
            $right = str_ends_with($cell, ':');
            if ($left && $right) {
                $alignments[] = 'center';
            } elseif ($right) {
                $alignments[] = 'right';
            } elseif ($left) {
                $alignments[] = 'left';
            } else {
                $alignments[] = '';
            }
        }

        // Parse header cells
        $headerCells = $this->splitTableRow($headerLine);

        // Column count must match
        if (count($headerCells) !== count($delimCells)) {
            return false; // Not a valid table
        }

        // Remove paragraph and replace with table
        $this->removeLastBlock();

        $tableNode = new ElementNode('table');
        $parent = $this->getCurrentParent();
        $parent->addChild($tableNode);

        // Header row
        $headerRow = new ElementNode('row', ['header' => true]);
        foreach ($headerCells as $i => $cell) {
            $cellNode = new ElementNode('cell', [
                'align' => $alignments[$i] ?? '',
                'header' => true,
            ]);
            $cellContent = trim($cell);
            if ($cellContent !== '') {
                $cellNode->addChild(new TextNode($cellContent));
            }
            $headerRow->addChild($cellNode);
        }
        $tableNode->addChild($headerRow);

        // Open the table block for data rows
        $tableBlock = new OpenBlock('table', $tableNode, $parent);
        $tableBlock->node->setAttribute('alignments', $alignments);
        $tableBlock->node->setAttribute('column_count', count($delimCells));
        $this->context->openBlock($tableBlock);

        return true;
    }

    /**
     * Split a table row into cells
     *
     * @return list<string>
     */
    private function splitTableRow(string $line): array
    {
        // Remove leading/trailing pipes
        $line = trim($line);
        if (str_starts_with($line, '|')) {
            $line = substr($line, 1);
        }
        if (str_ends_with($line, '|') && !str_ends_with($line, '\\|')) {
            $line = substr($line, 0, -1);
        }

        // Split by unescaped pipes
        $cells = [];
        $current = '';
        for ($i = 0; $i < strlen($line); $i++) {
            if ($line[$i] === '\\' && $i + 1 < strlen($line) && $line[$i + 1] === '|') {
                // \| in table cells: unescape to literal |
                $current .= '|';
                $i++;
                continue;
            }
            if ($line[$i] === '|') {
                $cells[] = $current;
                $current = '';
                continue;
            }
            $current .= $line[$i];
        }
        $cells[] = $current;

        return $cells;
    }

    /**
     * Check if a line starts a block-level construct
     *
     * Returns true for ATX headings, thematic breaks, fenced code,
     * blockquotes, and list items.
     */
    private function isBlockStartLine(string $line): bool
    {
        $trimmed = ltrim($line);
        $indent = strlen($line) - strlen($trimmed);
        if ($indent >= 4) {
            return false;
        }

        // ATX heading
        if (preg_match('/^#{1,6}(?:\s|$)/', $trimmed)) {
            return true;
        }
        // Thematic break
        if (preg_match('/^(?:(?:\*\s*){3,}|(?:-\s*){3,}|(?:_\s*){3,})\s*$/', $trimmed)) {
            return true;
        }
        // Fenced code
        if (preg_match('/^(?:`{3,}|~{3,})/', $trimmed)) {
            return true;
        }
        // Blockquote
        if (preg_match('/^>\s?/', $trimmed)) {
            return true;
        }
        // Bullet list item
        if (preg_match('/^[*+-]\s/', $trimmed)) {
            return true;
        }
        // Ordered list item
        if (preg_match('/^\d{1,9}[.)]\s/', $trimmed)) {
            return true;
        }

        return false;
    }

    // ---------------------------------------------------------------
    // Link reference definition
    // ---------------------------------------------------------------

    /**
     * Try to extract link reference definitions from paragraph content
     *
     * @return string Remaining content after removing definitions
     */
    private function extractLinkReferences(string $content): string
    {
        while (true) {
            $result = $this->tryParseLinkReference($content);
            if ($result === null) {
                break;
            }
            [$label, $destination, $title, $remaining] = $result;
            $this->context->getReferenceMap()->add($label, $destination, $title);
            $content = $remaining;
        }
        return $content;
    }

    /**
     * @return array{string, string, string, string}|null [label, dest, title, remaining]
     */
    private function tryParseLinkReference(string $content): ?array
    {
        // [label]: destination "title"
        // Must start at position 0 (beginning of paragraph content)

        if (!str_starts_with($content, '[')) {
            return null;
        }

        // Find the matching ] for the label, handling backslash escapes
        $pos = 1;
        $label = '';
        $len = strlen($content);
        while ($pos < $len) {
            if ($content[$pos] === '\\' && $pos + 1 < $len) {
                $label .= $content[$pos] . $content[$pos + 1];
                $pos += 2;
                continue;
            }
            if ($content[$pos] === ']') {
                break;
            }
            if ($content[$pos] === '[') {
                return null; // Unescaped [ in label
            }
            $label .= $content[$pos];
            $pos++;
        }
        if ($pos >= $len || $content[$pos] !== ']') {
            return null;
        }
        $pos++; // skip ]

        // Must be followed by :
        if ($pos >= $len || $content[$pos] !== ':') {
            return null;
        }
        $pos++; // skip :

        if (trim($label) === '' || strlen($label) > 999) {
            return null;
        }

        // Skip optional spaces/tabs and up to one newline
        while ($pos < $len && ($content[$pos] === ' ' || $content[$pos] === "\t")) {
            $pos++;
        }
        if ($pos < $len && $content[$pos] === "\n") {
            $pos++;
            while ($pos < $len && ($content[$pos] === ' ' || $content[$pos] === "\t")) {
                $pos++;
            }
        }

        $rest = substr($content, $pos);
        $destination = '';
        $title = '';

        // Parse destination
        if ($rest === '') {
            return null;
        }

        if (str_starts_with($rest, '<')) {
            // Angle-bracketed destination — cannot contain newlines or unescaped < >
            if (preg_match('/^<([^\n<>]*)>/', $rest, $dm)) {
                $destination = $dm[1];
                $rest = substr($rest, strlen($dm[0]));
            } else {
                return null;
            }
        } else {
            // Non-angle-bracketed destination — balanced parens, no spaces/newlines
            $dest = '';
            $parenDepth = 0;
            $i = 0;
            $rlen = strlen($rest);
            while ($i < $rlen && $rest[$i] !== ' ' && $rest[$i] !== "\t" && $rest[$i] !== "\n") {
                if ($rest[$i] === '\\' && $i + 1 < $rlen) {
                    $dest .= $rest[$i] . $rest[$i + 1];
                    $i += 2;
                    continue;
                }
                if ($rest[$i] === '(') {
                    $parenDepth++;
                } elseif ($rest[$i] === ')') {
                    if ($parenDepth === 0) {
                        break;
                    }
                    $parenDepth--;
                }
                $dest .= $rest[$i];
                $i++;
            }
            if ($dest === '' || $parenDepth !== 0) {
                return null;
            }
            $destination = $dest;
            $rest = substr($rest, $i);
        }

        // Check for title — must be separated from destination by whitespace
        // Title can be on same line (with space) or next line
        $savedRest = $rest;

        // Skip spaces/tabs on current line
        $spacesOnLine = '';
        $j = 0;
        while ($j < strlen($rest) && ($rest[$j] === ' ' || $rest[$j] === "\t")) {
            $spacesOnLine .= $rest[$j];
            $j++;
        }
        $afterSpaces = substr($rest, $j);

        // Check end of line
        if ($afterSpaces === '' || $afterSpaces[0] === "\n") {
            // End of line — title might be on next line
            if ($afterSpaces !== '' && $afterSpaces[0] === "\n") {
                $titleLine = ltrim(substr($afterSpaces, 1), " \t");
                if ($titleLine !== '' && ($titleLine[0] === '"' || $titleLine[0] === "'" || $titleLine[0] === '(')) {
                    if (preg_match('/^(?:"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\'|\(([^)\\\\]*(?:\\\\.[^)\\\\]*)*)\))/', $titleLine, $tm)) {
                        $title = $tm[1] !== '' ? $tm[1] : ($tm[2] !== '' ? $tm[2] : ($tm[3] ?? ''));
                        $rest = substr($titleLine, strlen($tm[0]));
                    } else {
                        // Not a valid title on next line — no title
                        $rest = $afterSpaces;
                    }
                } else {
                    // No title on next line
                    $rest = $afterSpaces;
                }
            } else {
                // End of content, no title
                $rest = '';
            }
        } elseif ($spacesOnLine !== '' && ($afterSpaces[0] === '"' || $afterSpaces[0] === "'" || $afterSpaces[0] === '(')) {
            // Title on same line, separated by whitespace
            if (preg_match('/^(?:"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\'|\(([^)\\\\]*(?:\\\\.[^)\\\\]*)*)\))/', $afterSpaces, $tm)) {
                $title = $tm[1] !== '' ? $tm[1] : ($tm[2] !== '' ? $tm[2] : ($tm[3] ?? ''));
                $rest = substr($afterSpaces, strlen($tm[0]));
            } else {
                return null; // Invalid title syntax
            }
        } elseif ($spacesOnLine === '' && $afterSpaces !== '') {
            // No space between dest and next char — invalid
            return null;
        }

        // Must be followed by newline or end (only spaces allowed on the rest of the line)
        $restOfLine = ltrim($rest, " \t");
        if ($restOfLine !== '' && $restOfLine[0] !== "\n") {
            // Title version failed (trailing content after title, or invalid title)
            // Fall back to no-title version: accept just the destination
            // The line after destination must end the reference
            $fallbackRest = $savedRest;
            $fallbackLine = ltrim($fallbackRest, " \t");
            if ($fallbackLine === '' || $fallbackLine[0] === "\n") {
                $remaining = ltrim($fallbackLine, "\n");
                $destination = $this->decodeHtmlEntities($this->unescapeString($destination));
                return [$label, $destination, '', $remaining];
            }
            return null;
        }

        $remaining = ltrim($restOfLine, "\n");
        $destination = $this->decodeHtmlEntities($this->unescapeString($destination));
        $title = $this->decodeHtmlEntities($this->unescapeString($title));
        // Unescape the label too (for display purposes, the map normalizes)
        return [$label, $destination, $title, $remaining];
    }

    // ---------------------------------------------------------------
    // Paragraph
    // ---------------------------------------------------------------

    private function handleParagraph(string $line, string $originalLine = ''): void
    {
        // Use original (tab-preserved) content for paragraph text
        $contentLine = ($originalLine !== '') ? $originalLine : $line;
        $content = ltrim($contentLine);
        if ($content === '') {
            return;
        }

        $tip = $this->context->getTip();

        // If in a table, parse as table row
        if ($tip !== null && $tip->type === 'table') {
            $this->addTableRow($tip, $line);
            return;
        }

        // Continue existing paragraph
        if ($tip !== null && $tip->type === 'paragraph') {
            $tip->appendContent($content);
            return;
        }

        // Start new paragraph
        $node = new ElementNode('paragraph');
        $parent = $this->getCurrentParent();
        $parent->addChild($node);

        $block = new OpenBlock('paragraph', $node, $parent);
        $block->appendContent($content);
        $this->context->openBlock($block);
    }

    private function handleParagraphInContainer(string $content, ElementNode $container): void
    {
        $content = ltrim($content);
        if ($content === '') {
            return;
        }

        $node = new ElementNode('paragraph');
        $container->addChild($node);

        $block = new OpenBlock('paragraph', $node, $container);
        $block->appendContent($content);
        $this->context->openBlock($block);
    }

    private function addTableRow(OpenBlock $table, string $line): void
    {
        $cells = $this->splitTableRow(trim($line));
        $alignments = $table->node->getAttributes()['alignments'] ?? [];
        $colCount = $table->node->getAttributes()['column_count'] ?? count($cells);

        // If blank line, close table
        if (trim($line) === '') {
            $table->close();
            $this->context->closeLastBlock();
            return;
        }

        // Check if line starts a new block structure (closes the table)
        $trimmed = ltrim($line);
        $indent = strlen($line) - strlen($trimmed);
        if ($indent < 4 && (
            preg_match('/^#{1,6}(\s|$)/', $trimmed)   // ATX heading
            || preg_match('/^(?:(?:\*\s*){3,}|(?:-\s*){3,}|(?:_\s*){3,})\s*$/', $trimmed) // Thematic break
            || preg_match('/^(?:`{3,}|~{3,})/', $trimmed)  // Fenced code
            || preg_match('/^>\s?/', $trimmed)                  // Blockquote
        )) {
            $table->close();
            $this->context->closeLastBlock();
            $this->handleParagraph($line);
            return;
        }

        // Parse cells (lines without | get treated as a single-cell row)
        $cells = $this->splitTableRow(trim($line));

        $row = new ElementNode('row');
        for ($i = 0; $i < $colCount; $i++) {
            $cellContent = isset($cells[$i]) ? trim($cells[$i]) : '';
            $cellNode = new ElementNode('cell', [
                'align' => $alignments[$i] ?? '',
            ]);
            if ($cellContent !== '') {
                $cellNode->addChild(new TextNode($cellContent));
            }
            $row->addChild($cellNode);
        }
        $table->node->addChild($row);
    }

    // ---------------------------------------------------------------
    // Blank line handling
    // ---------------------------------------------------------------

    private function handleBlankLine(): void
    {
        $tip = $this->context->getTip();

        if ($tip === null) {
            return;
        }

        // Mark list items for tight/loose detection.
        // For each ancestor listitem, record whether the blank line
        // occurred while a nested list or blockquote was open inside the item.
        // Also record child count at blank time to detect if the item
        // gained new children after the blank (indicating blank was
        // between direct children).
        $openBlocks = $this->context->getOpenBlocks();
        $insideNestedContainer = false;
        // Walk from tip to root
        for ($i = count($openBlocks) - 1; $i >= 0; $i--) {
            $block = $openBlocks[$i];
            if (!$block->isOpen()) {
                continue;
            }
            if ($block->type === 'list' || $block->type === 'blockquote') {
                $insideNestedContainer = true;
            }
            if ($block->type === 'listitem') {
                $block->node->setAttribute('had_blank_line', true);
                $block->node->setAttribute('children_at_blank', count($block->node->getChildren()));
                if ($insideNestedContainer) {
                    $block->node->setAttribute('blank_in_nested', true);
                }
                // Reset for next ancestor
                $insideNestedContainer = false;
            }
        }

        // Empty list items (no content) are valid per spec.
        // A blank line after them closes the item (so content after the blank
        // doesn't get absorbed), but keeps the list open for more items.
        if ($tip->type === 'listitem' && count($tip->node->getChildren()) === 0) {
            $this->closeListItem($tip);
            return;
        }

        if ($tip->type === 'paragraph') {
            $this->finalizeParagraph($tip);
            return;
        }

        if ($tip->type === 'indented_code') {
            $tip->appendContent('');
            return;
        }

        if ($tip->type === 'table') {
            $tip->close();
            $this->context->closeLastBlock();
            return;
        }

        if ($tip->type === 'html_block') {
            $htmlType = $tip->node->getAttributes()['html_block_type'] ?? 0;
            if ($htmlType === 6 || $htmlType === 7) {
                $this->finalizeHtmlBlock($tip);
            } else {
                $tip->appendContent('');
            }
        }
    }

    // ---------------------------------------------------------------
    // Finalization helpers
    // ---------------------------------------------------------------

    private function closeParagraphIfOpen(): void
    {
        $tip = $this->context->getTip();
        if ($tip !== null && $tip->type === 'paragraph') {
            $this->finalizeParagraph($tip);
        }
    }

    private function finalizeParagraph(OpenBlock $block): void
    {
        $content = $block->getContent();

        // Try to extract link reference definitions
        $content = $this->extractLinkReferences($content);
        $content = trim($content);

        if ($content === '') {
            // Paragraph was entirely link reference definitions — remove the node
            $this->removeNodeFromParent($block->node, $block->parent);
        } else {
            $block->setContent($content);
            // Set the text content
            $block->node->addChild(new TextNode($content));
        }

        $block->close();
        $this->context->closeLastBlock();
    }

    private function finalizeDocument(): void
    {
        // Close all remaining open blocks
        while ($tip = $this->context->getTip()) {
            match ($tip->type) {
                'paragraph' => $this->finalizeParagraph($tip),
                'fenced_code' => $this->closeFencedCode($tip),
                'indented_code' => $this->finalizeIndentedCode($tip),
                'html_block' => $this->finalizeHtmlBlock($tip),
                'listitem' => $this->closeListItem($tip),
                'list' => $this->closeList($tip),
                'table' => (function () use ($tip) {
                    $tip->close();
                    $this->context->closeLastBlock();
                })(),
                default => (function () use ($tip) {
                    $tip->close();
                    $this->context->closeLastBlock();
                })(),
            };
        }
    }

    private function getCurrentParent(): DocumentNode|ElementNode
    {
        $tip = $this->context->getTip();
        if ($tip === null) {
            return $this->context->getDocument();
        }

        // For container blocks (blockquote, list, listitem), add children to the node
        if (in_array($tip->type, ['blockquote', 'list', 'listitem'], true)) {
            return $tip->node;
        }

        return $this->context->getDocument();
    }

    private function addToDocument(ElementNode $node): void
    {
        $parent = $this->getCurrentParent();
        $parent->addChild($node);
    }

    /**
     * Remove the last open block and its node from the parent
     */
    private function removeLastBlock(): void
    {
        $block = $this->context->closeLastBlock();
        if ($block !== null) {
            $this->removeNodeFromParent($block->node, $block->parent);
        }
    }

    private function removeNodeFromParent(ElementNode $node, $parent): void
    {
        if ($parent instanceof DocumentNode || $parent instanceof ElementNode) {
            $parent->removeChild($node);
        }
    }

    private function decodeHtmlEntities(string $str): string
    {
        return html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function unescapeString(string $str): string
    {
        return preg_replace_callback(
            '/\\\\([!"#$%&\'()*+,\-.\\/:;<=>?@\[\\\\\\]^_`{|}~])/',
            fn(array $m) => $m[1],
            $str,
        ) ?? $str;
    }

    /**
     * Remove up to N columns of indentation, handling tabs
     *
     * @param string $line The line to remove indent from
     * @param int $columns Number of columns to remove
     * @param int $startColumn Starting virtual column (for correct tab-stop computation)
     */
    private function removeIndent(string $line, int $columns, int $startColumn = 0): string
    {
        $removed = 0;
        $pos = 0;
        $len = strlen($line);
        $currentColumn = $startColumn;

        while ($removed < $columns && $pos < $len) {
            if ($line[$pos] === "\t") {
                $tabWidth = 4 - ($currentColumn % 4);
                if ($removed + $tabWidth <= $columns) {
                    $removed += $tabWidth;
                    $currentColumn += $tabWidth;
                    $pos++;
                } else {
                    // Partial tab: replace with remaining spaces
                    $consumed = $columns - $removed;
                    // Replace the tab with spaces minus what we consumed
                    return str_repeat(' ', $tabWidth - $consumed) . substr($line, $pos + 1);
                }
            } elseif ($line[$pos] === ' ') {
                $removed++;
                $currentColumn++;
                $pos++;
            } else {
                break;
            }
        }

        return substr($line, $pos);
    }

    /**
     * Check if a node "ends with a blank line" per cmark algorithm.
     * A node ends with a blank line if it has last_line_blank set,
     * or if its last child ends with a blank line (recursively).
     * Used for tight/loose list detection.
     */
    private function endsWithBlankLine(ElementNode $node): bool
    {
        if ($node->getAttributes()['last_line_blank'] ?? false) {
            return true;
        }
        $children = $node->getChildren();
        if (empty($children)) {
            return false;
        }
        $last = end($children);
        if ($last instanceof ElementNode) {
            $name = $last->getName();
            // Recurse into lists and list items
            if ($name === 'list' || $name === 'listitem' || $name === 'blockquote') {
                return $this->endsWithBlankLine($last);
            }
        }
        return false;
    }
}
