<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Doku;

use Horde\Text\Wiki\Token;
use Horde\Text\Wiki\TokenType;
use Horde\Text\Wiki\Tokenizer;

/**
 * DokuWiki tokenizer
 *
 * Converts DokuWiki markup into a token stream.
 *
 * DokuWiki core syntax:
 * - Bold: **text**
 * - Italic: //text//
 * - Underline: __text__
 * - Monospace: ''text''
 * - Strikethrough: <del>text</del>
 * - Superscript: <sup>text</sup>
 * - Subscript: <sub>text</sub>
 * - Headings: ====== H1 ====== to == H5 == (inverted: more = = higher)
 * - Links: [[url|text]] or bare http://...
 * - Images: {{src|alt}} with alignment via spaces
 * - Lists: 2-space indent + * (bullet) or - (numbered)
 * - Tables: | data cell | and ^ header cell ^
 * - Code block: <code>...</code>
 * - Nowiki: <nowiki>...</nowiki> or %%...%%
 * - Horizontal rule: ---- on own line
 * - Break: \\ (double backslash)
 * - Blockquote: > prefix
 * - Center: ::text::
 * - Deflist: ; term ; definition
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class DokuTokenizer implements Tokenizer
{
    /** @var array<array{pattern: string, handler: string}> */
    private array $blockPatterns;

    private string $inlinePattern;

    /** URL schemes recognized by this tokenizer */
    private const URL_SCHEMES = [
        'http://', 'https://', 'ftp://', 'gopher://', 'news://', 'mailto:',
    ];

    public function __construct()
    {
        $this->buildPatterns();
    }

    /**
     * Tokenize DokuWiki markup
     *
     * @param string $text Source text
     *
     * @return iterable<Token> Token stream
     */
    public function tokenize(string $text): iterable
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        $pos = 0;
        $length = strlen($text);

        while ($pos < $length) {
            $atLineStart = ($pos === 0 || $text[$pos - 1] === "\n");

            $blockMatch = null;
            $inlineMatch = null;

            // Try block patterns at line start
            if ($atLineStart) {
                $blockMatch = $this->matchBlock($text, $pos);
            }

            // Try inline pattern
            $inlineMatch = $this->matchInline($text, $pos);

            if ($blockMatch !== null) {
                foreach ($blockMatch['tokens'] as $token) {
                    yield $token;
                }
                $pos = $blockMatch['end'];
                continue;
            }

            if ($inlineMatch !== null && $inlineMatch['start'] === $pos) {
                foreach ($inlineMatch['tokens'] as $token) {
                    yield $token;
                }
                $pos = $inlineMatch['end'];
                continue;
            }

            // Emit text up to the next match or end of line
            $nextPos = $length;

            if ($inlineMatch !== null) {
                $nextPos = min($nextPos, $inlineMatch['start']);
            }

            $nextLine = $this->nextLineStart($text, $pos);
            if ($nextLine !== null) {
                $nextPos = min($nextPos, $nextLine);
            }

            if ($nextPos > $pos) {
                $chunk = substr($text, $pos, $nextPos - $pos);
                yield from $this->textTokens($chunk, $pos);
                $pos = $nextPos;
            } elseif ($inlineMatch !== null) {
                $chunk = substr($text, $pos, $inlineMatch['start'] - $pos);
                if ($chunk !== '') {
                    yield from $this->textTokens($chunk, $pos);
                }
                foreach ($inlineMatch['tokens'] as $token) {
                    yield $token;
                }
                $pos = $inlineMatch['end'];
            } else {
                $chunk = substr($text, $pos);
                yield from $this->textTokens($chunk, $pos);
                break;
            }
        }
    }

    // ---------------------------------------------------------------
    // Pattern building
    // ---------------------------------------------------------------

    private function buildPatterns(): void
    {
        // Block patterns — tried in order at line start
        $this->blockPatterns = [
            // Code block: <code>...\n</code> or <code lang>...\n</code>
            ['pattern' => '/\G<code(?:\s+([^>]*))?>[ \t]*\n(.*?)\n<\/code>(?:\n|$)/si', 'handler' => 'tokenizeCode'],
            // Nowiki block: <nowiki>\n...\n</nowiki>
            ['pattern' => '/\G<nowiki>[ \t]*\n(.*?)\n<\/nowiki>(?:\n|$)/si', 'handler' => 'tokenizeRaw'],
            // Heading: == to ====== with mandatory matching closing markers
            ['pattern' => '/\G(={2,6}) (.*?) \1\s*$(?:\n|$)/m', 'handler' => 'tokenizeHeading'],
            // Horizontal rule: four or more dashes
            ['pattern' => '/\G-{4,}\s*$(?:\n|$)/m', 'handler' => 'tokenizeHoriz'],
            // Blockquote: > prefixed lines
            ['pattern' => '/\G(>+ ?.*(?:\n|$))+/', 'handler' => 'tokenizeBlockquote'],
            // List: 2-space indentation + * or - marker
            ['pattern' => '/\G((?: {2})+[\*\-] .*(?:\n|$))+/', 'handler' => 'tokenizeList'],
            // Table: rows starting with | or ^
            ['pattern' => '/\G([\|\^][^\n]*[\|\^]\s*(?:\n|$))+/', 'handler' => 'tokenizeTable'],
            // Center: ::text:: on line
            ['pattern' => '/\G::(.*?)::\s*$(?:\n|$)/m', 'handler' => 'tokenizeCenter'],
            // Deflist: ; term ; definition lines
            ['pattern' => '/\G(;[^\n]*\n?)+/', 'handler' => 'tokenizeDeflist'],
        ];

        // Inline patterns — combined into single alternation
        // Uses ~ as regex delimiter to avoid conflicts with %% nowiki syntax
        $schemes = implode('|', array_map(
            fn($s) => preg_quote($s, '~'),
            self::URL_SCHEMES
        ));

        $this->inlinePattern = '~'
            . '(?:'
            // 1: Inline nowiki: %%text%%
            . '%%(.*?)%%'
            . '|'
            // 2,3: Described link: [[target|text]] or [[target]]
            . '\[\[(.+?)(?:\|(.+?))?\]\]'
            . '|'
            // 4: Bare URL
            . '(' . $schemes . ')(?:[^ \t\n"\'\[\]]*[A-Za-z0-9/?=&\x7e_#])'
            . '|'
            // 5,6: Image: {{src|alt}} or {{src}}
            . '\{\{([^}|]+?)(?:\|([^}]*))?\}\}'
            . '|'
            // 7: Bold: **text**
            . '\*\*(.+?)\*\*'
            . '|'
            // 8: Italic: //text//
            . '\/\/(.+?)\/\/'
            . '|'
            // 9: Underline: __text__
            . '__(.+?)__'
            . '|'
            // 10: Monospace: ''text'' (double single-quotes)
            . "''(.+?)''"
            . '|'
            // 11: Superscript: <sup>text</sup>
            . '<sup>(.+?)<\/sup>'
            . '|'
            // 12: Subscript: <sub>text</sub>
            . '<sub>(.+?)<\/sub>'
            . '|'
            // 13: Strikethrough: <del>text</del>
            . '<del>(.+?)<\/del>'
            . '|'
            // 14: Break: \\\\ (double backslash)
            . '(\\\\\\\\)'
            . ')~si';
    }

    // ---------------------------------------------------------------
    // Block matching
    // ---------------------------------------------------------------

    /**
     * @return array{tokens: array<Token>, end: int, match: string}|null
     */
    private function matchBlock(string $text, int $pos): ?array
    {
        foreach ($this->blockPatterns as $entry) {
            if (preg_match($entry['pattern'], $text, $matches, 0, $pos)) {
                $handler = $entry['handler'];
                $result = $this->$handler($matches, $pos);
                $result['match'] = $matches[0];
                return $result;
            }
        }
        return null;
    }

    // ---------------------------------------------------------------
    // Inline matching
    // ---------------------------------------------------------------

    /**
     * @return array{tokens: array<Token>, start: int, end: int}|null
     */
    private function matchInline(string $text, int $pos): ?array
    {
        if (!preg_match($this->inlinePattern, $text, $matches, PREG_OFFSET_CAPTURE, $pos)) {
            return null;
        }

        $matchStart = $matches[0][1];
        $matchEnd = $matchStart + strlen($matches[0][0]);

        $tokens = $this->tokenizeInlineMatch($matches, $matchStart);

        return ['tokens' => $tokens, 'start' => $matchStart, 'end' => $matchEnd];
    }

    // ---------------------------------------------------------------
    // Inline tokenization dispatch
    // ---------------------------------------------------------------

    /**
     * @param array $matches PREG_OFFSET_CAPTURE matches
     * @param int   $pos     Position of match start
     *
     * @return array<Token>
     */
    private function tokenizeInlineMatch(array $matches, int $pos): array
    {
        // Group 1: Inline nowiki %%text%%
        if (isset($matches[1]) && $matches[1][1] !== -1) {
            return [
                new Token(TokenType::TEXT, $matches[1][0], $pos),
            ];
        }

        // Group 2,3: Described link [[target|text]] or [[target]]
        if (isset($matches[2]) && $matches[2][1] !== -1) {
            $target = $matches[2][0];
            $text = (isset($matches[3]) && $matches[3][1] !== -1) ? $matches[3][0] : '';
            return $this->tokenizeLink($target, $text, $pos);
        }

        // Group 4: Bare URL
        if (isset($matches[4]) && $matches[4][1] !== -1) {
            $href = $matches[0][0];
            return [
                new Token(TokenType::OPEN_TAG, 'url', $pos, ['href' => $href]),
                new Token(TokenType::TEXT, $href, $pos),
                new Token(TokenType::CLOSE_TAG, 'url', $pos),
            ];
        }

        // Group 5,6: Image {{src|alt}}
        if (isset($matches[5]) && $matches[5][1] !== -1) {
            $src = trim($matches[5][0]);
            $alt = (isset($matches[6]) && $matches[6][1] !== -1) ? trim($matches[6][0]) : '';
            $attrs = ['src' => $src];
            if ($alt !== '') {
                $attrs['alt'] = $alt;
            }
            return [
                new Token(TokenType::OPEN_TAG, 'image', $pos, $attrs),
            ];
        }

        // Group 7: Bold
        if (isset($matches[7]) && $matches[7][1] !== -1) {
            return $this->wrapInline('bold', $matches[7][0], $pos);
        }

        // Group 8: Italic
        if (isset($matches[8]) && $matches[8][1] !== -1) {
            return $this->wrapInline('italic', $matches[8][0], $pos);
        }

        // Group 9: Underline
        if (isset($matches[9]) && $matches[9][1] !== -1) {
            return $this->wrapInline('underline', $matches[9][0], $pos);
        }

        // Group 10: Monospace (Tt)
        if (isset($matches[10]) && $matches[10][1] !== -1) {
            return $this->wrapInline('tt', $matches[10][0], $pos);
        }

        // Group 11: Superscript
        if (isset($matches[11]) && $matches[11][1] !== -1) {
            return $this->wrapInline('superscript', $matches[11][0], $pos);
        }

        // Group 12: Subscript
        if (isset($matches[12]) && $matches[12][1] !== -1) {
            return $this->wrapInline('subscript', $matches[12][0], $pos);
        }

        // Group 13: Strikethrough (<del>)
        if (isset($matches[13]) && $matches[13][1] !== -1) {
            return $this->wrapInline('del', $matches[13][0], $pos);
        }

        // Group 14: Break
        if (isset($matches[14]) && $matches[14][1] !== -1) {
            return [
                new Token(TokenType::OPEN_TAG, 'break', $pos),
            ];
        }

        return [];
    }

    // ---------------------------------------------------------------
    // Link tokenization
    // ---------------------------------------------------------------

    /**
     * Tokenize a DokuWiki link: [[target|text]]
     *
     * If target contains a URL scheme → url tag
     * If target contains > → interwiki (treated as wikilink for now)
     * Otherwise → wikilink tag
     */
    private function tokenizeLink(string $target, string $text, int $pos): array
    {
        $isUrl = false;
        foreach (self::URL_SCHEMES as $scheme) {
            if (str_starts_with($target, $scheme)) {
                $isUrl = true;
                break;
            }
        }

        if (!$isUrl && str_starts_with($target, '/')) {
            $isUrl = true;
        }

        if ($isUrl) {
            $displayText = ($text !== '') ? $text : $target;
            return [
                new Token(TokenType::OPEN_TAG, 'url', $pos, ['href' => $target]),
                new Token(TokenType::TEXT, $displayText, $pos),
                new Token(TokenType::CLOSE_TAG, 'url', $pos),
            ];
        }

        // Wikilink — split target on # for anchor
        $anchor = '';
        $page = $target;
        if (str_contains($target, '#')) {
            [$page, $anchor] = explode('#', $target, 2);
        }

        $displayText = ($text !== '') ? $text : $page;
        $attrs = ['page' => $page];
        if ($anchor !== '') {
            $attrs['anchor'] = $anchor;
        }

        return [
            new Token(TokenType::OPEN_TAG, 'wikilink', $pos, $attrs),
            new Token(TokenType::TEXT, $displayText, $pos),
            new Token(TokenType::CLOSE_TAG, 'wikilink', $pos),
        ];
    }

    // ---------------------------------------------------------------
    // Block tokenizers
    // ---------------------------------------------------------------

    /**
     * Tokenize code block: <code>...</code>
     *
     * @return array{tokens: array<Token>, end: int}
     */
    private function tokenizeCode(array $matches, int $pos): array
    {
        $content = $matches[2];
        $end = $pos + strlen($matches[0]);
        $attrs = [];

        // Optional language attribute
        if (isset($matches[1]) && trim($matches[1]) !== '') {
            $attrs['type'] = trim($matches[1]);
        }

        return [
            'tokens' => [
                new Token(TokenType::OPEN_TAG, 'code', $pos, $attrs),
                new Token(TokenType::TEXT, $content, $pos),
                new Token(TokenType::CLOSE_TAG, 'code', $pos),
            ],
            'end' => $end,
        ];
    }

    /**
     * Tokenize raw block: <nowiki>...</nowiki>
     *
     * @return array{tokens: array<Token>, end: int}
     */
    private function tokenizeRaw(array $matches, int $pos): array
    {
        $content = $matches[1];
        $end = $pos + strlen($matches[0]);

        return [
            'tokens' => [
                new Token(TokenType::OPEN_TAG, 'raw', $pos),
                new Token(TokenType::TEXT, $content, $pos),
                new Token(TokenType::CLOSE_TAG, 'raw', $pos),
            ],
            'end' => $end,
        ];
    }

    /**
     * Tokenize heading: ====== H1 ====== to == H5 ==
     *
     * DokuWiki inverts the heading level: more = means higher heading.
     * ====== = H1, ===== = H2, ==== = H3, === = H4, == = H5
     *
     * @return array{tokens: array<Token>, end: int}
     */
    private function tokenizeHeading(array $matches, int $pos): array
    {
        $eqCount = strlen($matches[1]);
        $level = 7 - $eqCount; // 6 = H1, 5 = H2, 4 = H3, 3 = H4, 2 = H5
        $text = trim($matches[2]);
        $end = $pos + strlen($matches[0]);

        return [
            'tokens' => [
                new Token(TokenType::OPEN_TAG, 'heading', $pos, ['level' => (string) $level]),
                new Token(TokenType::TEXT, $text, $pos),
                new Token(TokenType::CLOSE_TAG, 'heading', $pos),
            ],
            'end' => $end,
        ];
    }

    /**
     * Tokenize horizontal rule: ---- (four or more dashes)
     *
     * @return array{tokens: array<Token>, end: int}
     */
    private function tokenizeHoriz(array $matches, int $pos): array
    {
        $end = $pos + strlen($matches[0]);

        return [
            'tokens' => [
                new Token(TokenType::OPEN_TAG, 'horiz', $pos),
            ],
            'end' => $end,
        ];
    }

    /**
     * Tokenize blockquote: > prefixed lines
     *
     * @return array{tokens: array<Token>, end: int}
     */
    private function tokenizeBlockquote(array $matches, int $pos): array
    {
        $block = $matches[0];
        $end = $pos + strlen($block);
        $lines = explode("\n", rtrim($block, "\n"));

        $tokens = [];
        $tokens[] = new Token(TokenType::OPEN_TAG, 'blockquote', $pos);

        $content = [];
        foreach ($lines as $line) {
            $stripped = preg_replace('/^>+ ?/', '', $line);
            $content[] = $stripped;
        }

        $tokens[] = new Token(TokenType::TEXT, implode("\n", $content), $pos);
        $tokens[] = new Token(TokenType::CLOSE_TAG, 'blockquote', $pos);

        return ['tokens' => $tokens, 'end' => $end];
    }

    /**
     * Tokenize list block — indentation-based nesting
     *
     * DokuWiki lists use 2-space indentation:
     *   * bullet item (level 1)
     *     * nested bullet (level 2)
     *   - numbered item (level 1)
     *     - nested numbered (level 2)
     *
     * @return array{tokens: array<Token>, end: int}
     */
    private function tokenizeList(array $matches, int $pos): array
    {
        $block = $matches[0];
        $end = $pos + strlen($block);

        $lines = explode("\n", rtrim($block, "\n"));
        $tokens = [];

        // Parse lines into (level, type, text) tuples
        $items = [];
        foreach ($lines as $line) {
            if (preg_match('/^((?:  )+)([\*\-]) (.*)$/', $line, $m)) {
                $level = (int) (strlen($m[1]) / 2);
                $type = ($m[2] === '-') ? 'number' : 'bullet';
                $items[] = [
                    'level' => $level,
                    'type' => $type,
                    'text' => $m[3],
                ];
            }
        }

        if (empty($items)) {
            return ['tokens' => [], 'end' => $end];
        }

        // Stack tracks open (list type) at each nesting level
        $stack = [];
        $hasOpenItem = false;

        foreach ($items as $item) {
            $level = $item['level'];
            $currentDepth = count($stack);

            if ($level > $currentDepth) {
                while (count($stack) < $level) {
                    $tokens[] = new Token(TokenType::OPEN_TAG, 'list', $pos, ['type' => $item['type']]);
                    $stack[] = $item['type'];
                }
                $tokens[] = new Token(TokenType::OPEN_TAG, 'listitem', $pos);
                $tokens[] = new Token(TokenType::TEXT, $item['text'], $pos);
                $hasOpenItem = true;
            } elseif ($level === $currentDepth) {
                if ($hasOpenItem) {
                    $tokens[] = new Token(TokenType::CLOSE_TAG, 'listitem', $pos);
                }
                $tokens[] = new Token(TokenType::OPEN_TAG, 'listitem', $pos);
                $tokens[] = new Token(TokenType::TEXT, $item['text'], $pos);
                $hasOpenItem = true;
            } else {
                if ($hasOpenItem) {
                    $tokens[] = new Token(TokenType::CLOSE_TAG, 'listitem', $pos);
                    $hasOpenItem = false;
                }

                while (count($stack) > $level) {
                    array_pop($stack);
                    $tokens[] = new Token(TokenType::CLOSE_TAG, 'list', $pos);
                    $tokens[] = new Token(TokenType::CLOSE_TAG, 'listitem', $pos);
                }

                $tokens[] = new Token(TokenType::OPEN_TAG, 'listitem', $pos);
                $tokens[] = new Token(TokenType::TEXT, $item['text'], $pos);
                $hasOpenItem = true;
            }
        }

        if ($hasOpenItem) {
            $tokens[] = new Token(TokenType::CLOSE_TAG, 'listitem', $pos);
        }

        while (!empty($stack)) {
            array_pop($stack);
            $tokens[] = new Token(TokenType::CLOSE_TAG, 'list', $pos);
        }

        return ['tokens' => $tokens, 'end' => $end];
    }

    /**
     * Tokenize table: | for data cells, ^ for header cells
     *
     * DokuWiki table rows use | and ^ as delimiters:
     * ^ Header 1 ^ Header 2 ^
     * | Cell 1   | Cell 2   |
     *
     * @return array{tokens: array<Token>, end: int}
     */
    private function tokenizeTable(array $matches, int $pos): array
    {
        $block = $matches[0];
        $end = $pos + strlen($block);
        $tokens = [];

        $tokens[] = new Token(TokenType::OPEN_TAG, 'table', $pos);

        $rows = explode("\n", rtrim($block, "\n"));

        foreach ($rows as $rowContent) {
            $rowContent = trim($rowContent);
            if ($rowContent === '') {
                continue;
            }

            $tokens[] = new Token(TokenType::OPEN_TAG, 'row', $pos);

            // Parse cells by splitting on | and ^ delimiters
            $cells = $this->parseTableRow($rowContent);

            foreach ($cells as $cell) {
                $tokens[] = new Token(TokenType::OPEN_TAG, 'cell', $pos, ['type' => $cell['type']]);
                $tokens[] = new Token(TokenType::TEXT, $cell['content'], $pos);
                $tokens[] = new Token(TokenType::CLOSE_TAG, 'cell', $pos);
            }

            $tokens[] = new Token(TokenType::CLOSE_TAG, 'row', $pos);
        }

        $tokens[] = new Token(TokenType::CLOSE_TAG, 'table', $pos);

        return ['tokens' => $tokens, 'end' => $end];
    }

    /**
     * Parse a DokuWiki table row into cells
     *
     * @return array<array{type: string, content: string}>
     */
    private function parseTableRow(string $row): array
    {
        $cells = [];
        $len = strlen($row);
        $i = 0;

        // Skip leading delimiter
        if ($i < $len && ($row[$i] === '|' || $row[$i] === '^')) {
            $i++;
        }

        while ($i < $len) {
            // Find cell type based on the delimiter that STARTED this cell
            // The previous delimiter determines this cell's type
            $prevDelim = $row[$i - 1] ?? '|';
            $cellType = ($prevDelim === '^') ? 'header' : 'data';

            // Find next delimiter
            $nextPipe = strpos($row, '|', $i);
            $nextCaret = strpos($row, '^', $i);

            $nextDelim = null;
            if ($nextPipe !== false && $nextCaret !== false) {
                $nextDelim = min($nextPipe, $nextCaret);
            } elseif ($nextPipe !== false) {
                $nextDelim = $nextPipe;
            } elseif ($nextCaret !== false) {
                $nextDelim = $nextCaret;
            }

            if ($nextDelim === null) {
                // No more delimiters — remaining content (if any)
                $content = trim(substr($row, $i));
                if ($content !== '') {
                    $cells[] = ['type' => $cellType, 'content' => $content];
                }
                break;
            }

            $content = trim(substr($row, $i, $nextDelim - $i));

            // Only add cell if there was actual content space (skip trailing delimiter)
            if ($nextDelim > $i || $content !== '') {
                $cells[] = ['type' => $cellType, 'content' => $content];
            }

            $i = $nextDelim + 1;

            // If we're at end after a delimiter, that was the trailing one
            if ($i >= $len) {
                break;
            }
        }

        return $cells;
    }

    /**
     * Tokenize center: ::text::
     *
     * @return array{tokens: array<Token>, end: int}
     */
    private function tokenizeCenter(array $matches, int $pos): array
    {
        $content = trim($matches[1]);
        $end = $pos + strlen($matches[0]);

        return [
            'tokens' => [
                new Token(TokenType::OPEN_TAG, 'center', $pos),
                new Token(TokenType::TEXT, $content, $pos),
                new Token(TokenType::CLOSE_TAG, 'center', $pos),
            ],
            'end' => $end,
        ];
    }

    /**
     * Tokenize definition list: ; term ; definition
     *
     * DokuWiki uses semicolons: ; term ; definition
     *
     * @return array{tokens: array<Token>, end: int}
     */
    private function tokenizeDeflist(array $matches, int $pos): array
    {
        $block = $matches[0];
        $end = $pos + strlen($block);
        $lines = explode("\n", rtrim($block, "\n"));

        $tokens = [];
        $tokens[] = new Token(TokenType::OPEN_TAG, 'deflist', $pos);

        foreach ($lines as $line) {
            // ; term ; definition
            if (preg_match('/^;\s*(.+?)\s*;\s*(.*)$/', $line, $m)) {
                $term = trim($m[1]);
                $definition = trim($m[2]);

                $tokens[] = new Token(TokenType::OPEN_TAG, 'defterm', $pos);
                $tokens[] = new Token(TokenType::TEXT, $term, $pos);
                $tokens[] = new Token(TokenType::CLOSE_TAG, 'defterm', $pos);

                if ($definition !== '') {
                    $tokens[] = new Token(TokenType::OPEN_TAG, 'defdef', $pos);
                    $tokens[] = new Token(TokenType::TEXT, $definition, $pos);
                    $tokens[] = new Token(TokenType::CLOSE_TAG, 'defdef', $pos);
                }
            }
        }

        $tokens[] = new Token(TokenType::CLOSE_TAG, 'deflist', $pos);

        return ['tokens' => $tokens, 'end' => $end];
    }

    // ---------------------------------------------------------------
    // Helper methods
    // ---------------------------------------------------------------

    /**
     * Wrap text in OPEN_TAG/TEXT/CLOSE_TAG
     *
     * @return array<Token>
     */
    private function wrapInline(string $tagName, string $content, int $pos): array
    {
        return [
            new Token(TokenType::OPEN_TAG, $tagName, $pos),
            new Token(TokenType::TEXT, $content, $pos),
            new Token(TokenType::CLOSE_TAG, $tagName, $pos),
        ];
    }

    /**
     * Emit TEXT and NEWLINE tokens for a plain text chunk
     *
     * @return iterable<Token>
     */
    private function textTokens(string $text, int $pos): iterable
    {
        $lines = explode("\n", $text);
        $currentPos = $pos;

        foreach ($lines as $i => $line) {
            if ($line !== '') {
                yield new Token(TokenType::TEXT, $line, $currentPos);
                $currentPos += strlen($line);
            }

            if ($i < count($lines) - 1) {
                yield new Token(TokenType::NEWLINE, "\n", $currentPos);
                $currentPos += 1;
            }
        }
    }

    /**
     * Find start of next line
     */
    private function nextLineStart(string $text, int $pos): ?int
    {
        $nlPos = strpos($text, "\n", $pos);
        return ($nlPos !== false) ? $nlPos + 1 : null;
    }
}
