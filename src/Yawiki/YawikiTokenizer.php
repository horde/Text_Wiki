<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Yawiki;

use Horde\Text\Wiki\Token;
use Horde\Text\Wiki\TokenType;
use Horde\Text\Wiki\Tokenizer;

/**
 * Yawiki tokenizer
 *
 * Converts Yawiki wiki markup into a token stream.
 *
 * Yawiki syntax (formerly "Default" wiki format in Text_Wiki):
 * - Bold: '''text'''
 * - Italic: ''text''
 * - Strong: **text**
 * - Emphasis: //text//
 * - Underline: __text__
 * - Monospace: {{text}}
 * - Superscript: ^^text^^
 * - Subscript: ,,text,,
 * - Headings: + to ++++++ at line start
 * - Lists: * or # with indentation
 * - Horizontal rule: ---- on own line
 * - Line break: _ (space underscore newline)
 * - Links: [http://url text], StudlyCaps, ((free link))
 * - Tables: || header || and | cell |
 * - Code: <code>...</code>
 * - Blockquotes: > text
 * - Images: [[image url options]]
 * - Center: = text (line start)
 * - Colortext: ##color|text##
 * - Anchors: [[# name]]
 * - TOC: [[toc]]
 * - Revise: @@---del+++ins@@
 *
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   Justin Patrin <papercrane@reversefold.com>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class YawikiTokenizer implements Tokenizer
{
    /**
     * Regex patterns for block-level elements (matched at line start)
     *
     * Each entry: ['pattern' => regex, 'handler' => method name]
     * Patterns are tried in order; first match wins.
     *
     * @var array<array{pattern: string, handler: string}>
     */
    private array $blockPatterns;

    /**
     * Regex patterns for inline elements
     *
     * Combined into a single alternation regex for efficiency.
     *
     * @var string
     */
    private string $inlinePattern;

    public function __construct()
    {
        $this->buildPatterns();
    }

    /**
     * Tokenize Yawiki markup
     *
     * @param string $text Source text
     *
     * @return iterable<Token> Token stream
     */
    public function tokenize(string $text): iterable
    {
        // Prefilter: normalize line endings
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        $pos = 0;
        $length = strlen($text);

        while ($pos < $length) {
            $atLineStart = ($pos === 0 || $text[$pos - 1] === "\n");

            // Try block-level patterns at line start
            if ($atLineStart) {
                $blockMatch = $this->matchBlock($text, $pos);
                if ($blockMatch !== null) {
                    yield from $blockMatch['tokens'];
                    $pos = $blockMatch['end'];
                    continue;
                }
            }

            // Try inline patterns anywhere
            $inlineMatch = $this->matchInline($text, $pos);
            if ($inlineMatch !== null && $inlineMatch['start'] === $pos) {
                yield from $inlineMatch['tokens'];
                $pos = $inlineMatch['end'];
                continue;
            }

            // Find the next match (block at next line start, or nearest inline)
            $nextBlockStart = $this->nextLineStart($text, $pos);
            $nextInlineStart = $inlineMatch !== null ? $inlineMatch['start'] : $length;

            // If there's an inline match before the next line, consume text up to it
            if ($inlineMatch !== null && $inlineMatch['start'] < $nextBlockStart) {
                // Emit text up to inline match
                if ($inlineMatch['start'] > $pos) {
                    yield from $this->textTokens($text, $pos, $inlineMatch['start']);
                }
                yield from $inlineMatch['tokens'];
                $pos = $inlineMatch['end'];
                continue;
            }

            // Consume text up to next line start (or end of text)
            $end = min($nextBlockStart, $length);
            if ($end === $pos) {
                // At a newline, emit it
                yield new Token(TokenType::NEWLINE, "\n", $pos);
                $pos++;
            } else {
                yield from $this->textTokens($text, $pos, $end);
                $pos = $end;
            }
        }
    }

    /**
     * Build regex patterns
     *
     * @return void
     */
    private function buildPatterns(): void
    {
        // Block patterns (tried at line start)
        $this->blockPatterns = [
            ['pattern' => '/\G(\+{1,6}) (.*)/m', 'handler' => 'tokenizeHeading'],
            ['pattern' => '/\G-{4,}[ \t]*(?:\n|$)/', 'handler' => 'tokenizeHoriz'],
            ['pattern' => '/\G<code(\s[^>]*)?>(.+?)\n<\/code>(?:\s|$)/si', 'handler' => 'tokenizeCode'],
            ['pattern' => '/\G\[\[toc(?:\s+(\d+))?\]\]/', 'handler' => 'tokenizeToc'],
            ['pattern' => '/\G\[\[#\s+([-_A-Za-z0-9.]+?)(?:\s+.+?)?\]\]/', 'handler' => 'tokenizeAnchor'],
            ['pattern' => '/\G\[\[image\s+(.+?)\]\]/i', 'handler' => 'tokenizeImage'],
            ['pattern' => '/\G((?:[ \t]*[*#] .*(?:\n|$))+)/', 'handler' => 'tokenizeList'],
            ['pattern' => '/\G((?:> .*(?:\n|$))+)/', 'handler' => 'tokenizeBlockquote'],
            ['pattern' => '/\G((?:\|\|.*(?:\n|$))+)/', 'handler' => 'tokenizeTable'],
            ['pattern' => '/\G((?:: .*(?:\n|$))+)/', 'handler' => 'tokenizeDeflist'],
            ['pattern' => '/\G= (.*?)(?:\n|$)/', 'handler' => 'tokenizeCenter'],
        ];

        // Inline pattern: combined alternation
        // Order matters: ''' before '', ** before *, etc.
        $this->inlinePattern = '/(?:'
            . "'''(.+?)'''"          // group 1: bold (triple quote)
            . "|''(.+?)''"           // group 2: italic (double quote)
            . '|\*\*(.+?)\*\*'      // group 3: strong
            . '|\/\/(.+?)\/\/'       // group 4: emphasis
            . '|__(.+?)__'          // group 5: underline
            . '|\{\{(.+?)\}\}'      // group 6: monospace
            . '|\^\^(.+?)\^\^'      // group 7: superscript
            . '|,,(.+?),,'          // group 8: subscript
            . '|##([a-zA-Z0-9#]+)\|(.+?)##' // groups 9,10: colortext
            . '|@@(.+?)@@'          // group 11: revise
            . '| _\n'               // group 12 implicit: line break
            . '|\[(\w+:\/\/[^\]\s]+)(?:\s+([^\]]+))?\]' // groups 12,13: url
            . '|\(\(([^\)]+)\)\)'   // group 14: freelink
            . '|\[\[php\s+(.+?)\]\]' // group 15: phplookup
            . ')/Us';
    }

    /**
     * Try matching a block-level pattern at position
     *
     * @param string $text Source text
     * @param int    $pos  Current position
     *
     * @return array{tokens: array<Token>, end: int}|null Match result
     */
    private function matchBlock(string $text, int $pos): ?array
    {
        $remaining = substr($text, $pos);

        foreach ($this->blockPatterns as $bp) {
            if (preg_match($bp['pattern'], $remaining, $matches)) {
                $handler = $bp['handler'];
                $tokens = $this->$handler($matches, $pos);
                return [
                    'tokens' => $tokens,
                    'end' => $pos + strlen($matches[0]),
                ];
            }
        }

        return null;
    }

    /**
     * Try matching an inline pattern starting at or after position
     *
     * @param string $text Source text
     * @param int    $pos  Current position
     *
     * @return array{tokens: array<Token>, start: int, end: int}|null Match result
     */
    private function matchInline(string $text, int $pos): ?array
    {
        if (!preg_match($this->inlinePattern, $text, $matches, PREG_OFFSET_CAPTURE, $pos)) {
            return null;
        }

        $matchStart = $matches[0][1];
        $matchFull = $matches[0][0];
        $tokens = $this->tokenizeInlineMatch($matches, $matchStart);

        return [
            'tokens' => $tokens,
            'start' => $matchStart,
            'end' => $matchStart + strlen($matchFull),
        ];
    }

    /**
     * Tokenize an inline regex match into tokens
     *
     * @param array $matches PREG_OFFSET_CAPTURE matches
     * @param int   $pos     Match start position
     *
     * @return array<Token> Tokens
     */
    private function tokenizeInlineMatch(array $matches, int $pos): array
    {
        $full = $matches[0][0];

        // Bold: '''text'''
        if (isset($matches[1]) && $matches[1][1] !== -1) {
            return $this->wrapInline('bold', $matches[1][0], $pos);
        }
        // Italic: ''text''
        if (isset($matches[2]) && $matches[2][1] !== -1) {
            return $this->wrapInline('italic', $matches[2][0], $pos);
        }
        // Strong: **text**
        if (isset($matches[3]) && $matches[3][1] !== -1) {
            return $this->wrapInline('strong', $matches[3][0], $pos);
        }
        // Emphasis: //text//
        if (isset($matches[4]) && $matches[4][1] !== -1) {
            return $this->wrapInline('emphasis', $matches[4][0], $pos);
        }
        // Underline: __text__
        if (isset($matches[5]) && $matches[5][1] !== -1) {
            return $this->wrapInline('underline', $matches[5][0], $pos);
        }
        // Monospace: {{text}}
        if (isset($matches[6]) && $matches[6][1] !== -1) {
            return $this->wrapInline('tt', $matches[6][0], $pos);
        }
        // Superscript: ^^text^^
        if (isset($matches[7]) && $matches[7][1] !== -1) {
            return $this->wrapInline('superscript', $matches[7][0], $pos);
        }
        // Subscript: ,,text,,
        if (isset($matches[8]) && $matches[8][1] !== -1) {
            return $this->wrapInline('subscript', $matches[8][0], $pos);
        }
        // Colortext: ##color|text##
        if (isset($matches[9]) && $matches[9][1] !== -1) {
            $color = $matches[9][0];
            $text = $matches[10][0];
            if (preg_match('/^[0-9A-Fa-f]{3,6}$/', $color)) {
                $color = '#' . $color;
            }
            return [
                new Token(TokenType::OPEN_TAG, 'color', $pos, ['color' => $color]),
                new Token(TokenType::TEXT, $text, $pos),
                new Token(TokenType::CLOSE_TAG, 'color', $pos),
            ];
        }
        // Revise: @@---del+++ins@@
        if (isset($matches[11]) && $matches[11][1] !== -1) {
            return $this->tokenizeRevise($matches[11][0], $pos);
        }
        // Line break: _ (space underscore newline)
        if (str_ends_with($full, " _\n")) {
            return [
                new Token(TokenType::OPEN_TAG, 'break', $pos),
            ];
        }
        // URL: [http://url text]
        if (isset($matches[12]) && $matches[12][1] !== -1) {
            $href = $matches[12][0];
            $text = (isset($matches[13]) && $matches[13][1] !== -1)
                ? $matches[13][0]
                : $href;
            return [
                new Token(TokenType::OPEN_TAG, 'url', $pos, ['href' => $href]),
                new Token(TokenType::TEXT, $text, $pos),
                new Token(TokenType::CLOSE_TAG, 'url', $pos),
            ];
        }
        // Freelink: ((page name))
        if (isset($matches[14]) && $matches[14][1] !== -1) {
            $content = $matches[14][0];
            $parts = explode('|', $content, 2);
            $page = trim($parts[0]);
            $text = isset($parts[1]) ? trim($parts[1]) : $page;
            return [
                new Token(TokenType::OPEN_TAG, 'wikilink', $pos, ['page' => $page]),
                new Token(TokenType::TEXT, $text, $pos),
                new Token(TokenType::CLOSE_TAG, 'wikilink', $pos),
            ];
        }
        // PHP lookup: [[php function]]
        if (isset($matches[15]) && $matches[15][1] !== -1) {
            return [
                new Token(TokenType::OPEN_TAG, 'phplookup', $pos, ['function' => $matches[15][0]]),
                new Token(TokenType::TEXT, $matches[15][0], $pos),
                new Token(TokenType::CLOSE_TAG, 'phplookup', $pos),
            ];
        }

        // Fallback: treat as text
        return [new Token(TokenType::TEXT, $full, $pos)];
    }

    // ---------------------------------------------------------------
    // Block-level tokenizer handlers
    // ---------------------------------------------------------------

    /**
     * Heading: + to ++++++ at line start
     *
     * @param array $matches Regex matches
     * @param int   $pos     Position
     *
     * @return array<Token>
     */
    private function tokenizeHeading(array $matches, int $pos): array
    {
        $level = strlen($matches[1]);
        $text = $matches[2];

        return [
            new Token(TokenType::OPEN_TAG, 'heading', $pos, ['level' => $level]),
            new Token(TokenType::TEXT, $text, $pos),
            new Token(TokenType::CLOSE_TAG, 'heading', $pos),
        ];
    }

    /**
     * Horizontal rule: ---- on own line
     *
     * @param array $matches Regex matches
     * @param int   $pos     Position
     *
     * @return array<Token>
     */
    private function tokenizeHoriz(array $matches, int $pos): array
    {
        return [
            new Token(TokenType::OPEN_TAG, 'horiz', $pos),
        ];
    }

    /**
     * Code block: <code>...</code>
     *
     * @param array $matches Regex matches
     * @param int   $pos     Position
     *
     * @return array<Token>
     */
    private function tokenizeCode(array $matches, int $pos): array
    {
        $attrs = [];
        // Parse attributes from <code type="php"> etc.
        if (isset($matches[1]) && $matches[1] !== '') {
            $attrStr = trim($matches[1]);
            if (preg_match('/type\s*=\s*["\']?(\w+)["\']?/', $attrStr, $am)) {
                $attrs['language'] = $am[1];
            }
        }
        $text = $matches[2];

        return [
            new Token(TokenType::OPEN_TAG, 'code', $pos, $attrs),
            new Token(TokenType::TEXT, $text, $pos),
            new Token(TokenType::CLOSE_TAG, 'code', $pos),
        ];
    }

    /**
     * Table of contents: [[toc]] or [[toc N]]
     *
     * @param array $matches Regex matches
     * @param int   $pos     Position
     *
     * @return array<Token>
     */
    private function tokenizeToc(array $matches, int $pos): array
    {
        $attrs = [];
        if (isset($matches[1]) && $matches[1] !== '') {
            $attrs['depth'] = (int)$matches[1];
        }

        return [
            new Token(TokenType::OPEN_TAG, 'toc', $pos, $attrs),
        ];
    }

    /**
     * Anchor: [[# name]]
     *
     * @param array $matches Regex matches
     * @param int   $pos     Position
     *
     * @return array<Token>
     */
    private function tokenizeAnchor(array $matches, int $pos): array
    {
        return [
            new Token(TokenType::OPEN_TAG, 'anchor', $pos, ['name' => $matches[1]]),
        ];
    }

    /**
     * Image: [[image url options]]
     *
     * @param array $matches Regex matches
     * @param int   $pos     Position
     *
     * @return array<Token>
     */
    private function tokenizeImage(array $matches, int $pos): array
    {
        $content = trim($matches[1]);
        $parts = preg_split('/\s+/', $content, 2);
        $src = $parts[0];
        $attrs = ['src' => $src];

        // Parse remaining options
        if (isset($parts[1])) {
            $optStr = $parts[1];
            if (preg_match('/alt="([^"]*)"/', $optStr, $am)) {
                $attrs['alt'] = $am[1];
            }
            if (preg_match('/link="([^"]*)"/', $optStr, $am)) {
                $attrs['link'] = $am[1];
            }
        }

        return [
            new Token(TokenType::OPEN_TAG, 'image', $pos, $attrs),
        ];
    }

    /**
     * Lists: * or # with indentation
     *
     * @param array $matches Regex matches
     * @param int   $pos     Position
     *
     * @return array<Token>
     */
    private function tokenizeList(array $matches, int $pos): array
    {
        $tokens = [];
        $block = $matches[1];

        preg_match_all('/^([ \t]*)([*#]) (.*)$/m', $block, $items, PREG_SET_ORDER);

        $stack = []; // Track list nesting depths
        $hasOpenItem = false; // Whether a listitem is open and awaiting close

        foreach ($items as $item) {
            $indent = strlen($item[1]);
            $type = $item[2] === '*' ? 'bullet' : 'number';
            $text = $item[3];

            $targetLevel = $indent + 1; // 0 indent = level 1

            if (count($stack) < $targetLevel) {
                // Going deeper — open new list levels (keep parent listitem open)
                while (count($stack) < $targetLevel) {
                    $tokens[] = new Token(TokenType::OPEN_TAG, 'list', $pos, ['type' => $type]);
                    $stack[] = $type;
                }
                $hasOpenItem = false;
            } elseif (count($stack) > $targetLevel) {
                // Going shallower — close the current listitem, then close excess lists+items
                if ($hasOpenItem) {
                    $tokens[] = new Token(TokenType::CLOSE_TAG, 'listitem', $pos);
                    $hasOpenItem = false;
                }
                while (count($stack) > $targetLevel) {
                    $tokens[] = new Token(TokenType::CLOSE_TAG, 'list', $pos);
                    array_pop($stack);
                    // Close the parent listitem that contained this sublist
                    $tokens[] = new Token(TokenType::CLOSE_TAG, 'listitem', $pos);
                }
            } else {
                // Same level — close previous listitem
                if ($hasOpenItem) {
                    $tokens[] = new Token(TokenType::CLOSE_TAG, 'listitem', $pos);
                    $hasOpenItem = false;
                }
            }

            // Open new listitem
            $tokens[] = new Token(TokenType::OPEN_TAG, 'listitem', $pos);
            $tokens[] = new Token(TokenType::TEXT, $text, $pos);
            $hasOpenItem = true;
        }

        // Close the last open listitem
        if ($hasOpenItem) {
            $tokens[] = new Token(TokenType::CLOSE_TAG, 'listitem', $pos);
        }

        // Close all remaining list levels
        while (count($stack) > 0) {
            $tokens[] = new Token(TokenType::CLOSE_TAG, 'list', $pos);
            array_pop($stack);
        }

        return $tokens;
    }

    /**
     * Blockquote: > text lines
     *
     * @param array $matches Regex matches
     * @param int   $pos     Position
     *
     * @return array<Token>
     */
    private function tokenizeBlockquote(array $matches, int $pos): array
    {
        $block = $matches[1];
        // Remove leading '> ' from each line
        $text = preg_replace('/^> /m', '', $block);

        return [
            new Token(TokenType::OPEN_TAG, 'blockquote', $pos),
            new Token(TokenType::TEXT, trim($text), $pos),
            new Token(TokenType::CLOSE_TAG, 'blockquote', $pos),
        ];
    }

    /**
     * Table: || delimited rows with cell attribute prefixes
     *
     * Original PEAR format: all cells split by ||
     * Cell prefixes: ~ (header), > (right), = (center), < (left)
     *
     * @param array $matches Regex matches
     * @param int   $pos     Position
     *
     * @return array<Token>
     */
    private function tokenizeTable(array $matches, int $pos): array
    {
        $tokens = [];
        $block = trim($matches[1]);
        $rows = explode("\n", $block);

        $tokens[] = new Token(TokenType::OPEN_TAG, 'table', $pos);

        foreach ($rows as $row) {
            $row = trim($row);
            if ($row === '') {
                continue;
            }

            // Split by || — first and last elements are empty (before/after outer ||)
            $cells = explode('||', $row);
            $last = count($cells) - 1;

            $tokens[] = new Token(TokenType::OPEN_TAG, 'row', $pos);

            for ($i = 1; $i < $last; $i++) {
                $cellContent = $cells[$i];

                // Skip empty cells (column spanning — future feature)
                if (trim($cellContent) === '') {
                    continue;
                }

                // Parse cell attribute prefix
                $attrs = ['type' => 'data'];
                $trimmed = $cellContent;

                // Check for 2-char prefix after leading whitespace
                $stripped = ltrim($cellContent);
                if (str_starts_with($stripped, '~ ')) {
                    $attrs['type'] = 'header';
                    $trimmed = substr($stripped, 2);
                } elseif (str_starts_with($stripped, '> ')) {
                    $attrs['align'] = 'right';
                    $trimmed = substr($stripped, 2);
                } elseif (str_starts_with($stripped, '= ')) {
                    $attrs['align'] = 'center';
                    $trimmed = substr($stripped, 2);
                } elseif (str_starts_with($stripped, '< ')) {
                    $attrs['align'] = 'left';
                    $trimmed = substr($stripped, 2);
                }

                $cellText = trim($trimmed);

                $tokens[] = new Token(TokenType::OPEN_TAG, 'cell', $pos, $attrs);
                $tokens[] = new Token(TokenType::TEXT, $cellText, $pos);
                $tokens[] = new Token(TokenType::CLOSE_TAG, 'cell', $pos);
            }

            $tokens[] = new Token(TokenType::CLOSE_TAG, 'row', $pos);
        }

        $tokens[] = new Token(TokenType::CLOSE_TAG, 'table', $pos);

        return $tokens;
    }

    /**
     * Definition list: : term : definition
     *
     * @param array $matches Regex matches
     * @param int   $pos     Position
     *
     * @return array<Token>
     */
    private function tokenizeDeflist(array $matches, int $pos): array
    {
        $tokens = [];
        $block = $matches[1];

        $tokens[] = new Token(TokenType::OPEN_TAG, 'deflist', $pos);

        preg_match_all('/^: (.+?) : (.*)$/m', $block, $items, PREG_SET_ORDER);

        foreach ($items as $item) {
            $tokens[] = new Token(TokenType::OPEN_TAG, 'defterm', $pos);
            $tokens[] = new Token(TokenType::TEXT, trim($item[1]), $pos);
            $tokens[] = new Token(TokenType::CLOSE_TAG, 'defterm', $pos);
            $tokens[] = new Token(TokenType::OPEN_TAG, 'defdef', $pos);
            $tokens[] = new Token(TokenType::TEXT, trim($item[2]), $pos);
            $tokens[] = new Token(TokenType::CLOSE_TAG, 'defdef', $pos);
        }

        $tokens[] = new Token(TokenType::CLOSE_TAG, 'deflist', $pos);

        return $tokens;
    }

    /**
     * Center: = text at line start
     *
     * @param array $matches Regex matches
     * @param int   $pos     Position
     *
     * @return array<Token>
     */
    private function tokenizeCenter(array $matches, int $pos): array
    {
        return [
            new Token(TokenType::OPEN_TAG, 'center', $pos),
            new Token(TokenType::TEXT, $matches[1], $pos),
            new Token(TokenType::CLOSE_TAG, 'center', $pos),
        ];
    }

    // ---------------------------------------------------------------
    // Inline helpers
    // ---------------------------------------------------------------

    /**
     * Wrap text in open/close tokens for a simple inline tag
     *
     * @param string $name Tag name
     * @param string $text Inner text
     * @param int    $pos  Position
     *
     * @return array<Token>
     */
    private function wrapInline(string $name, string $text, int $pos): array
    {
        return [
            new Token(TokenType::OPEN_TAG, $name, $pos),
            new Token(TokenType::TEXT, $text, $pos),
            new Token(TokenType::CLOSE_TAG, $name, $pos),
        ];
    }

    /**
     * Tokenize @@revision@@ content
     *
     * Supports @@---deleted+++inserted@@ syntax
     *
     * @param string $content Content between @@
     * @param int    $pos     Position
     *
     * @return array<Token>
     */
    private function tokenizeRevise(string $content, int $pos): array
    {
        $tokens = [];

        // Try to split on --- and +++
        if (preg_match('/^---(.+?)\+\+\+(.+?)$/', $content, $parts)) {
            $tokens[] = new Token(TokenType::OPEN_TAG, 'revise_del', $pos);
            $tokens[] = new Token(TokenType::TEXT, $parts[1], $pos);
            $tokens[] = new Token(TokenType::CLOSE_TAG, 'revise_del', $pos);
            $tokens[] = new Token(TokenType::OPEN_TAG, 'revise_ins', $pos);
            $tokens[] = new Token(TokenType::TEXT, $parts[2], $pos);
            $tokens[] = new Token(TokenType::CLOSE_TAG, 'revise_ins', $pos);
        } elseif (str_starts_with($content, '---')) {
            $tokens[] = new Token(TokenType::OPEN_TAG, 'revise_del', $pos);
            $tokens[] = new Token(TokenType::TEXT, substr($content, 3), $pos);
            $tokens[] = new Token(TokenType::CLOSE_TAG, 'revise_del', $pos);
        } elseif (str_starts_with($content, '+++')) {
            $tokens[] = new Token(TokenType::OPEN_TAG, 'revise_ins', $pos);
            $tokens[] = new Token(TokenType::TEXT, substr($content, 3), $pos);
            $tokens[] = new Token(TokenType::CLOSE_TAG, 'revise_ins', $pos);
        } else {
            // Treat whole thing as insert
            $tokens[] = new Token(TokenType::OPEN_TAG, 'revise_ins', $pos);
            $tokens[] = new Token(TokenType::TEXT, $content, $pos);
            $tokens[] = new Token(TokenType::CLOSE_TAG, 'revise_ins', $pos);
        }

        return $tokens;
    }

    // ---------------------------------------------------------------
    // Text emission helpers
    // ---------------------------------------------------------------

    /**
     * Emit text/newline tokens for a substring
     *
     * @param string $text  Full source text
     * @param int    $start Start offset
     * @param int    $end   End offset (exclusive)
     *
     * @return array<Token>
     */
    private function textTokens(string $text, int $start, int $end): array
    {
        $tokens = [];
        $chunk = substr($text, $start, $end - $start);
        $pos = $start;

        $lines = explode("\n", $chunk);
        foreach ($lines as $i => $line) {
            if ($line !== '') {
                $tokens[] = new Token(TokenType::TEXT, $line, $pos);
                $pos += strlen($line);
            }
            if ($i < count($lines) - 1) {
                $tokens[] = new Token(TokenType::NEWLINE, "\n", $pos);
                $pos++;
            }
        }

        return $tokens;
    }

    /**
     * Find position of the start of the next line after $pos
     *
     * @param string $text Source text
     * @param int    $pos  Current position
     *
     * @return int Position of next line start, or text length
     */
    private function nextLineStart(string $text, int $pos): int
    {
        $nl = strpos($text, "\n", $pos);
        return $nl === false ? strlen($text) : $nl + 1;
    }
}
