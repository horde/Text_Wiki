<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Creole;

use Horde\Text\Wiki\Token;
use Horde\Text\Wiki\TokenType;
use Horde\Text\Wiki\Tokenizer;

/**
 * Creole tokenizer
 *
 * Converts Creole wiki markup into a token stream.
 *
 * Creole 1.0 core syntax:
 * - Bold: **text**
 * - Italic: //text//
 * - Headings: = to ====== (optional closing = stripped)
 * - Lists: * or # with repeated chars for nesting
 * - Horizontal rule: ---- on own line
 * - Code block: {{{ ... }}} on own lines
 * - Inline nowiki: {{{text}}}
 * - Links: [[url|text]] or bare http://...
 * - Images: {{img.png|alt}}
 * - Tables: | cell | cell per row, |= header
 * - Break: \\ (double backslash)
 * - Escape: ~char
 *
 * Horde extensions:
 * - Underline: __text__
 * - Superscript: ^^text^^
 * - Subscript: ,,text,,
 * - Blockquote: > or : prefix
 * - Center: ! text
 * - Deflist: ; term : definition
 *
 * @author   Michele Tomaiuolo <tomamic@yahoo.it>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class CreoleTokenizer implements Tokenizer
{
    /** @var array<array{pattern: string, handler: string}> */
    private array $blockPatterns;

    private string $inlinePattern;

    /** URL schemes recognized by this tokenizer */
    private const URL_SCHEMES = [
        'http://', 'https://', 'ftp://', 'ftps://', 'mailto:',
    ];

    public function __construct()
    {
        $this->buildPatterns();
    }

    /**
     * Tokenize Creole markup
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
            // Code block: {{{ on own line, content, }}} on own line
            ['pattern' => '/\G\{\{\{\n(.*?)\n\}\}\}/s', 'handler' => 'tokenizeCode'],
            // Heading: = to ====== at line start, optional trailing =
            ['pattern' => '/\G(={1,6}) *(.*?) *=*$(?:\n|$)/m', 'handler' => 'tokenizeHeading'],
            // Horizontal rule: four or more dashes
            ['pattern' => '/\G-{4,}$(?:\n|$)/m', 'handler' => 'tokenizeHoriz'],
            // Blockquote: > or : prefixed lines
            ['pattern' => '/\G([>:]+ ?.*(?:\n|$))+/', 'handler' => 'tokenizeBlockquote'],
            // List: *, -, or # with repetition for nesting
            ['pattern' => '/\G([\*#\-]+ .*(?:\n|$))+/', 'handler' => 'tokenizeList'],
            // Table: pipe-delimited rows
            ['pattern' => '/\G(\|.*(?:\n|$))+/', 'handler' => 'tokenizeTable'],
            // Center: ! prefix at line start
            ['pattern' => '/\G! *(.*?)$(?:\n|$)/m', 'handler' => 'tokenizeCenter'],
            // Deflist: ;term:definition lines
            ['pattern' => '/\G(;[^\n]*\n?)+/', 'handler' => 'tokenizeDeflist'],
        ];

        // Inline patterns — combined into single alternation
        // Uses % as regex delimiter to avoid conflicts
        $schemes = implode('|', array_map(
            fn($s) => preg_quote($s, '%'),
            self::URL_SCHEMES
        ));

        $this->inlinePattern = '%'
            . '(?:'
            // 1: Inline Tt/nowiki: {{{text}}} (must come before image)
            . '\{\{\{(.+?)\}\}\}'
            . '|'
            // 2,3: Described link: [[target|text]] or [[target]]
            . '\[\[(.+?)(?:\|(.+?))?\]\]'
            . '|'
            // 4: Bare URL
            . '(' . $schemes . ')(?:[^ \t\n"\'\[\]]*[A-Za-z0-9/?=&~_#])'
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
            // 10: Superscript: ^^text^^
            . '\^\^(.+?)\^\^'
            . '|'
            // 11: Subscript: ,,text,,
            . ',,(.+?),,'
            . '|'
            // 12: Break: \\\\ (double backslash) or %%%
            . '(\\\\\\\\|\%\%\%)'
            . '|'
            // 13: Escape: ~char
            . '~([^ \w\n])'
            . ')%s';
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
        // Group 1: Inline Tt {{{text}}}
        if (isset($matches[1]) && $matches[1][1] !== -1) {
            return $this->wrapInline('tt', $matches[1][0], $pos);
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

        // Group 10: Superscript
        if (isset($matches[10]) && $matches[10][1] !== -1) {
            return $this->wrapInline('superscript', $matches[10][0], $pos);
        }

        // Group 11: Subscript
        if (isset($matches[11]) && $matches[11][1] !== -1) {
            return $this->wrapInline('subscript', $matches[11][0], $pos);
        }

        // Group 12: Break
        if (isset($matches[12]) && $matches[12][1] !== -1) {
            return [
                new Token(TokenType::OPEN_TAG, 'break', $pos),
            ];
        }

        // Group 13: Escape ~char — emit the escaped character as text
        if (isset($matches[13]) && $matches[13][1] !== -1) {
            return [
                new Token(TokenType::TEXT, $matches[13][0], $pos),
            ];
        }

        return [];
    }

    // ---------------------------------------------------------------
    // Link tokenization
    // ---------------------------------------------------------------

    /**
     * Tokenize a Creole link: [[target|text]]
     *
     * If target contains a URL scheme → url tag
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

        // Also treat /path as URL (absolute path)
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
     * Tokenize code block: {{{ ... }}}
     *
     * @return array{tokens: array<Token>, end: int}
     */
    private function tokenizeCode(array $matches, int $pos): array
    {
        $content = $matches[1];
        $end = $pos + strlen($matches[0]);

        return [
            'tokens' => [
                new Token(TokenType::OPEN_TAG, 'code', $pos),
                new Token(TokenType::TEXT, $content, $pos),
                new Token(TokenType::CLOSE_TAG, 'code', $pos),
            ],
            'end' => $end,
        ];
    }

    /**
     * Tokenize heading: = to ====== at line start
     *
     * @return array{tokens: array<Token>, end: int}
     */
    private function tokenizeHeading(array $matches, int $pos): array
    {
        $level = strlen($matches[1]);
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
     * Tokenize blockquote: > or : prefixed lines
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
            $stripped = preg_replace('/^[>:]+ ?/', '', $line);
            $content[] = $stripped;
        }

        $tokens[] = new Token(TokenType::TEXT, implode("\n", $content), $pos);
        $tokens[] = new Token(TokenType::CLOSE_TAG, 'blockquote', $pos);

        return ['tokens' => $tokens, 'end' => $end];
    }

    /**
     * Tokenize list block — character repetition for nesting
     *
     * * item (level 1 bullet)
     * ** item (level 2 bullet)
     * # item (level 1 numbered)
     * - item (level 1 bullet, dash variant)
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
            if (preg_match('/^([\*#\-]+)\s*(.*)$/', $line, $m)) {
                $markers = $m[1];
                $level = strlen($markers);
                $lastChar = $markers[strlen($markers) - 1];
                $type = ($lastChar === '#') ? 'number' : 'bullet';
                $items[] = [
                    'level' => $level,
                    'type' => $type,
                    'text' => $m[2],
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
                // Going deeper — open new list levels
                while (count($stack) < $level) {
                    $tokens[] = new Token(TokenType::OPEN_TAG, 'list', $pos, ['type' => $item['type']]);
                    $stack[] = $item['type'];
                }
                $tokens[] = new Token(TokenType::OPEN_TAG, 'listitem', $pos);
                $tokens[] = new Token(TokenType::TEXT, $item['text'], $pos);
                $hasOpenItem = true;
            } elseif ($level === $currentDepth) {
                // Same level — close previous item, open new
                if ($hasOpenItem) {
                    $tokens[] = new Token(TokenType::CLOSE_TAG, 'listitem', $pos);
                }
                $tokens[] = new Token(TokenType::OPEN_TAG, 'listitem', $pos);
                $tokens[] = new Token(TokenType::TEXT, $item['text'], $pos);
                $hasOpenItem = true;
            } else {
                // Going shallower — close items and lists
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

        // Close remaining open items and lists
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
     * Tokenize table: pipe-delimited rows
     *
     * | cell | cell (data row)
     * |= header |= header (header row)
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

            // Remove leading and optional trailing pipe
            if (str_starts_with($rowContent, '|')) {
                $rowContent = substr($rowContent, 1);
            }
            if (str_ends_with($rowContent, '|')) {
                $rowContent = substr($rowContent, 0, -1);
            }

            if ($rowContent === '') {
                continue;
            }

            $tokens[] = new Token(TokenType::OPEN_TAG, 'row', $pos);

            // Split cells by pipe
            $cells = explode('|', $rowContent);

            foreach ($cells as $cellContent) {
                $cellContent = trim($cellContent);

                $cellAttrs = ['type' => 'data'];

                // Check for header marker: = prefix
                if (str_starts_with($cellContent, '=')) {
                    $cellAttrs['type'] = 'header';
                    $cellContent = trim(substr($cellContent, 1));
                }

                $tokens[] = new Token(TokenType::OPEN_TAG, 'cell', $pos, $cellAttrs);
                $tokens[] = new Token(TokenType::TEXT, $cellContent, $pos);
                $tokens[] = new Token(TokenType::CLOSE_TAG, 'cell', $pos);
            }

            $tokens[] = new Token(TokenType::CLOSE_TAG, 'row', $pos);
        }

        $tokens[] = new Token(TokenType::CLOSE_TAG, 'table', $pos);

        return ['tokens' => $tokens, 'end' => $end];
    }

    /**
     * Tokenize center: ! text
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
     * Tokenize definition list: ;term:definition
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
            if (preg_match('/^;\s*(.+?)(?:\s*:\s*(.*))?$/', $line, $m)) {
                $term = trim($m[1]);
                $definition = isset($m[2]) ? trim($m[2]) : '';

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
