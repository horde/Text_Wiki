<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Cowiki;

use Horde\Text\Wiki\Token;
use Horde\Text\Wiki\TokenType;
use Horde\Text\Wiki\Tokenizer;

/**
 * Cowiki tokenizer
 *
 * Converts Cowiki wiki markup into a token stream.
 *
 * Cowiki syntax:
 * - Bold: *text*
 * - Italic: /text/
 * - Underline: _text_
 * - Monospace: =text=
 * - Superscript: <sup>text</sup>
 * - Subscript: <sub>text</sub>
 * - Headings: + to ++++++ at line start
 * - Lists: * or # with indentation
 * - Horizontal rule: --- on own line
 * - Code: <code>...</code>
 * - Raw: <noop>...</noop>
 * - Blockquote: > text at line start
 * - Table: <table><tr><td>...</td></tr></table>
 * - TOC: <toc>
 * - URLs: inline http://..., described ((url)(text))
 * - Wikilinks: ((PageName)(text))
 *
 * @author   Daniel T. Gorski
 * @author   Justin Patrin <papercrane@reversefold.com>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class CowikiTokenizer implements Tokenizer
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
     * Tokenize Cowiki markup
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

            // Decide: take whichever starts earliest
            if ($blockMatch !== null && $inlineMatch !== null) {
                // Block always starts at $pos; inline might start later
                if ($inlineMatch['start'] < $pos + strlen($blockMatch['match'])) {
                    // Block wins if it starts at current position
                    $match = $blockMatch;
                } else {
                    $match = null; // both apply — prefer block at line start
                }
            }

            if ($blockMatch !== null) {
                // Emit text before block (shouldn't be any if atLineStart)
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
                // Inline match is ahead — emit text up to it
                $chunk = substr($text, $pos, $inlineMatch['start'] - $pos);
                if ($chunk !== '') {
                    yield from $this->textTokens($chunk, $pos);
                }
                foreach ($inlineMatch['tokens'] as $token) {
                    yield $token;
                }
                $pos = $inlineMatch['end'];
            } else {
                // No match at all — emit rest as text
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
            // Code block: <code>...</code>
            ['pattern' => '/\G(<code(?:\s[^>]*)?>)\n(.*?)\n(<\/code>)/s', 'handler' => 'tokenizeCode'],
            // Raw/noop: <noop>...</noop>
            ['pattern' => '/\G<noop>(.*?)<\/noop>/s', 'handler' => 'tokenizeRaw'],
            // TOC: <toc> (optional attributes)
            ['pattern' => '/\G<toc(?:\s[^>]*)?>/', 'handler' => 'tokenizeToc'],
            // Table: <table>...</table>
            ['pattern' => '/\G<table[^>]*>(.*?)<\/table>/s', 'handler' => 'tokenizeTable'],
            // Heading: + to ++++++ followed by space and text
            ['pattern' => '/\G(\+{1,6}) (.*)/', 'handler' => 'tokenizeHeading'],
            // Horizontal rule: three or more dashes
            ['pattern' => '/\G-{3,}$(?:\n|$)/m', 'handler' => 'tokenizeHoriz'],
            // Blockquote: > prefixed lines
            ['pattern' => '/\G(>+ .*(?:\n|$))+/', 'handler' => 'tokenizeBlockquote'],
            // List: * or # with optional indentation
            ['pattern' => '/\G([ ]*[*#] .*(?:\n|$))+/', 'handler' => 'tokenizeList'],
        ];

        // Inline patterns — combined into single alternation
        // Uses # as regex delimiter to avoid conflicts with URLs containing /
        $schemes = implode('|', array_map(
            fn($s) => preg_quote($s, '#'),
            self::URL_SCHEMES
        ));

        $this->inlinePattern = '#'
            . '(?:'
            // 1: Superscript: <sup>text</sup>
            . '<sup>(.*?)</sup>'
            . '|'
            // 2: Subscript: <sub>text</sub>
            . '<sub>(.*?)</sub>'
            . '|'
            // 3,4: Described link: ((target)(text))
            . '\(\((.+?)\)\((.+?)\)\)'
            . '|'
            // 5: Bare paren link: ((target))
            . '\(\((.+?)\)\)'
            . '|'
            // 6: Inline URL
            . '(' . $schemes . ')(?:[^ \t\n"\'()]*[A-Za-z0-9/?=&~_\#])'
            . '|'
            // 7: Bold: *text*
            . '\*([^*\n]+?)\*'
            . '|'
            // 8: Italic: /text/ (not after <)
            . '(?<!<)/([^/\n]+?)/'
            . '|'
            // 9: Underline: _text_
            . '(?<![A-Za-z0-9])_([^_\n]+?)_(?![A-Za-z0-9])'
            . '|'
            // 10: Monospace: =text=
            . '(?<![A-Za-z0-9])=([^=\n]+?)=(?![A-Za-z0-9])'
            . ')#';
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
        // Group 1: Superscript
        if (isset($matches[1]) && $matches[1][1] !== -1) {
            return $this->wrapInline('superscript', $matches[1][0], $pos);
        }

        // Group 2: Subscript
        if (isset($matches[2]) && $matches[2][1] !== -1) {
            return $this->wrapInline('subscript', $matches[2][0], $pos);
        }

        // Group 3,4: Described link ((target)(text))
        if (isset($matches[3]) && $matches[3][1] !== -1) {
            $target = $matches[3][0];
            $text = $matches[4][0];
            return $this->tokenizeLink($target, $text, $pos);
        }

        // Group 5: Bare paren link ((target))
        if (isset($matches[5]) && $matches[5][1] !== -1) {
            $target = $matches[5][0];
            return $this->tokenizeLink($target, '', $pos);
        }

        // Group 6: Inline URL
        if (isset($matches[6]) && $matches[6][1] !== -1) {
            $href = $matches[0][0];
            return [
                new Token(TokenType::OPEN_TAG, 'url', $pos, ['href' => $href]),
                new Token(TokenType::TEXT, $href, $pos),
                new Token(TokenType::CLOSE_TAG, 'url', $pos),
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

        // Group 10: Monospace
        if (isset($matches[10]) && $matches[10][1] !== -1) {
            return $this->wrapInline('tt', $matches[10][0], $pos);
        }

        return [];
    }

    // ---------------------------------------------------------------
    // Link tokenization
    // ---------------------------------------------------------------

    /**
     * Tokenize a Cowiki link target
     *
     * If target contains a URL scheme → url tag with href attribute
     * Otherwise → wikilink tag with page attribute
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
     * @return array{tokens: array<Token>, end: int}
     */
    private function tokenizeCode(array $matches, int $pos): array
    {
        $openTag = $matches[1];
        $content = $matches[2];
        $end = $pos + strlen($matches[0]);

        // Parse optional language from <code type="php">
        $attrs = [];
        if (preg_match('/type="([^"]+)"/', $openTag, $typeMatch)) {
            $attrs['language'] = $typeMatch[1];
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
     * @return array{tokens: array<Token>, end: int}
     */
    private function tokenizeToc(array $matches, int $pos): array
    {
        $end = $pos + strlen($matches[0]);

        return [
            'tokens' => [
                new Token(TokenType::OPEN_TAG, 'toc', $pos),
            ],
            'end' => $end,
        ];
    }

    /**
     * @return array{tokens: array<Token>, end: int}
     */
    private function tokenizeTable(array $matches, int $pos): array
    {
        $content = $matches[1];
        $end = $pos + strlen($matches[0]);
        $tokens = [];

        $tokens[] = new Token(TokenType::OPEN_TAG, 'table', $pos);

        // Parse rows: <tr>...</tr>
        if (preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si', $content, $rowMatches)) {
            foreach ($rowMatches[1] as $rowContent) {
                $tokens[] = new Token(TokenType::OPEN_TAG, 'row', $pos);

                // Parse cells: <th>...</th> and <td>...</td>
                if (preg_match_all('/<(th|td)([^>]*)>(.*?)<\/\1>/si', $rowContent, $cellMatches, PREG_SET_ORDER)) {
                    foreach ($cellMatches as $cellMatch) {
                        $tagName = strtolower($cellMatch[1]);
                        $attrString = $cellMatch[2];
                        $cellContent = $cellMatch[3];

                        $cellAttrs = [];
                        if ($tagName === 'th') {
                            $cellAttrs['type'] = 'header';
                        } else {
                            $cellAttrs['type'] = 'data';
                        }

                        // Parse colspan
                        if (preg_match('/colspan="(\d+)"/i', $attrString, $colspanMatch)) {
                            $cellAttrs['span'] = $colspanMatch[1];
                        }

                        // Parse alignment
                        if (preg_match('/align="([^"]+)"/i', $attrString, $alignMatch)) {
                            $cellAttrs['align'] = strtolower($alignMatch[1]);
                        }

                        $tokens[] = new Token(TokenType::OPEN_TAG, 'cell', $pos, $cellAttrs);
                        $tokens[] = new Token(TokenType::TEXT, trim($cellContent), $pos);
                        $tokens[] = new Token(TokenType::CLOSE_TAG, 'cell', $pos);
                    }
                }

                $tokens[] = new Token(TokenType::CLOSE_TAG, 'row', $pos);
            }
        }

        $tokens[] = new Token(TokenType::CLOSE_TAG, 'table', $pos);

        return ['tokens' => $tokens, 'end' => $end];
    }

    /**
     * @return array{tokens: array<Token>, end: int}
     */
    private function tokenizeHeading(array $matches, int $pos): array
    {
        $level = strlen($matches[1]);
        $text = $matches[2];
        $end = $pos + strlen($matches[0]);

        // Consume trailing newline if present
        if ($end < strlen($text) || true) {
            // The regex doesn't capture newline, advance past it
        }

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
            // Strip leading > and optional space
            $stripped = preg_replace('/^>+ ?/', '', $line);
            $content[] = $stripped;
        }

        $tokens[] = new Token(TokenType::TEXT, implode("\n", $content), $pos);
        $tokens[] = new Token(TokenType::CLOSE_TAG, 'blockquote', $pos);

        return ['tokens' => $tokens, 'end' => $end];
    }

    /**
     * Tokenize list block — deferred listitem closing for proper nesting
     *
     * @return array{tokens: array<Token>, end: int}
     */
    private function tokenizeList(array $matches, int $pos): array
    {
        $block = $matches[0];
        $end = $pos + strlen($block);

        $lines = explode("\n", rtrim($block, "\n"));
        $tokens = [];

        // Parse lines into (indent, type, text) tuples
        $items = [];
        foreach ($lines as $line) {
            if (preg_match('/^( *)([\*#]) (.*)$/', $line, $m)) {
                $items[] = [
                    'indent' => strlen($m[1]),
                    'type' => ($m[2] === '#') ? 'number' : 'bullet',
                    'text' => $m[3],
                ];
            }
        }

        if (empty($items)) {
            return ['tokens' => [], 'end' => $end];
        }

        // Normalize indents to levels (0, 1, 2, ...)
        $indents = array_unique(array_column($items, 'indent'));
        sort($indents);
        $indentToLevel = array_flip($indents);

        // Stack tracks open (list type) at each nesting level
        $stack = [];
        $hasOpenItem = false;

        foreach ($items as $item) {
            $level = $indentToLevel[$item['indent']];
            $currentDepth = count($stack);

            if ($level >= $currentDepth) {
                // Going deeper — open new list levels
                while (count($stack) <= $level) {
                    $tokens[] = new Token(TokenType::OPEN_TAG, 'list', $pos, ['type' => $item['type']]);
                    $stack[] = $item['type'];
                }
                // Open new listitem
                $tokens[] = new Token(TokenType::OPEN_TAG, 'listitem', $pos);
                $tokens[] = new Token(TokenType::TEXT, $item['text'], $pos);
                $hasOpenItem = true;
            } elseif ($level === $currentDepth - 1) {
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

                while (count($stack) > $level + 1) {
                    array_pop($stack);
                    $tokens[] = new Token(TokenType::CLOSE_TAG, 'list', $pos);
                    // Close the parent listitem that contained this sublist
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
