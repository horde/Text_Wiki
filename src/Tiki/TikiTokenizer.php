<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Tiki;

use Horde\Text\Wiki\Token;
use Horde\Text\Wiki\TokenType;
use Horde\Text\Wiki\Tokenizer;

/**
 * Tiki tokenizer
 *
 * Converts Tiki wiki markup into a token stream.
 *
 * Tiki syntax:
 * - Bold: __text__
 * - Italic: ''text''
 * - Underline: ===text===
 * - Monospace: -+text+-
 * - Superscript: ^^text^^
 * - Subscript: ,,text,,
 * - Colortext: ~~color:text~~
 * - Headings: ! to !!!!!! at line start
 * - Lists: * or # with repeated chars for nesting
 * - Horizontal rule: ---- on own line
 * - Code: {CODE()}...{CODE}
 * - Raw: ~np~...~/np~
 * - Blockquote: > text at line start
 * - Table: ||cell1|cell2||
 * - TOC: {toc} or {maketoc}
 * - Center: ::text::
 * - Deflist: ;term:definition
 * - URLs: [url|text] or inline http://...
 * - Wikilinks: ((Page|text))
 * - Image: {img src="url"}
 * - Anchor: [[# name]]
 * - Break: _\n (underscore at end of line)
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class TikiTokenizer implements Tokenizer
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
     * Tokenize Tiki markup
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
            // Code block: {CODE(attrs)}...{CODE}
            ['pattern' => '/\G\{CODE\(([^)]*)\)\}\n(.*?)\n\{CODE\}/si', 'handler' => 'tokenizeCode'],
            // Raw: ~np~...~/np~
            ['pattern' => '/\G~np~(.*?)~\/np~/s', 'handler' => 'tokenizeRaw'],
            // TOC: {toc} or {maketoc}
            ['pattern' => '/\G\{(?:make)?toc\}/i', 'handler' => 'tokenizeToc'],
            // Image: {img src="..." ...}
            ['pattern' => '/\G\{img\s+(.+?)\s*\}/i', 'handler' => 'tokenizeImage'],
            // Heading: ! to !!!!!! followed by optional collapse marker and text
            ['pattern' => '/\G(!{1,6})([^\n]*)/', 'handler' => 'tokenizeHeading'],
            // Horizontal rule: four or more dashes
            ['pattern' => '/\G-{4,}$(?:\n|$)/m', 'handler' => 'tokenizeHoriz'],
            // Blockquote: > prefixed lines
            ['pattern' => '/\G(>+ .*(?:\n|$))+/', 'handler' => 'tokenizeBlockquote'],
            // List: * or # with repetition for nesting
            ['pattern' => '/\G((?:\*+|#+).*(?:\n|$))+/', 'handler' => 'tokenizeList'],
            // Table: ||...|| (rows wrapped in double pipes)
            ['pattern' => '/\G\|\|(.*?)\|\|/s', 'handler' => 'tokenizeTable'],
            // Center: ::text::
            ['pattern' => '/\G::(.*?)::/s', 'handler' => 'tokenizeCenter'],
            // Deflist: ;term:definition lines
            ['pattern' => '/\G(;[^\n]*\n?)+/', 'handler' => 'tokenizeDeflist'],
        ];

        // Inline patterns — combined into single alternation
        $schemes = implode('|', array_map(
            fn($s) => preg_quote($s, '%'),
            self::URL_SCHEMES
        ));

        $this->inlinePattern = '%'
            . '(?:'
            // 1: Anchor: [[# name]]
            . '\[\[#\s+([-_A-Za-z0-9.]+?)(?:\s+(.+?))?\]\]'
            . '|'
            // 3,4: Described URL: [url|text]
            . '\[([^\[\]]*?)\|([^\[\]]+?)\]'
            . '|'
            // 5: Inline URL
            . '(' . $schemes . ')(?:[^ \t\n"\'()\[\]]*[A-Za-z0-9/?=&~_#])'
            . '|'
            // 6,7: Described wikilink: ((page|text))
            . '\(\((.+?)\|(.+?)\)\)'
            . '|'
            // 8: Bare wikilink: ((page))
            . '\(\((.+?)\)\)'
            . '|'
            // 9: Bold: __text__
            . '__([^_\n]+?)__'
            . '|'
            // 10: Italic: \'\'text\'\'
            . "''([^'\\n]+?)''"
            . '|'
            // 11: Underline: ===text===
            . '===([^=\n]+?)==='
            . '|'
            // 12: Monospace: -\+text\+-
            . '-\+(.+?)\+-'
            . '|'
            // 13: Superscript: ^^text^^
            . '\^\^([^\^\n]+?)\^\^'
            . '|'
            // 14: Subscript: ,,text,,
            . ',,([^,\n]+?),,'
            . '|'
            // 15,16: Colortext: ~~color:text~~
            . '~~([^~\n:]+?):([^~\n]+?)~~'
            . '|'
            // 17: Break: _\n (trailing underscore before newline)
            . ' _\n'
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
        // Group 1: Anchor name (group 2 is optional anchor text)
        if (isset($matches[1]) && $matches[1][1] !== -1) {
            $name = $matches[1][0];
            return [
                new Token(TokenType::OPEN_TAG, 'anchor', $pos, ['name' => $name]),
            ];
        }

        // Group 3,4: Described URL [url|text]
        if (isset($matches[3]) && $matches[3][1] !== -1) {
            $href = trim($matches[3][0]);
            $text = trim($matches[4][0]);
            return $this->tokenizeDescribedUrl($href, $text, $pos);
        }

        // Group 5: Inline URL
        if (isset($matches[5]) && $matches[5][1] !== -1) {
            $href = $matches[0][0];
            return [
                new Token(TokenType::OPEN_TAG, 'url', $pos, ['href' => $href]),
                new Token(TokenType::TEXT, $href, $pos),
                new Token(TokenType::CLOSE_TAG, 'url', $pos),
            ];
        }

        // Group 6,7: Described wikilink ((page|text))
        if (isset($matches[6]) && $matches[6][1] !== -1) {
            $target = $matches[6][0];
            $text = $matches[7][0];
            return $this->tokenizeWikilink($target, $text, $pos);
        }

        // Group 8: Bare wikilink ((page))
        if (isset($matches[8]) && $matches[8][1] !== -1) {
            $target = $matches[8][0];
            return $this->tokenizeWikilink($target, '', $pos);
        }

        // Group 9: Bold
        if (isset($matches[9]) && $matches[9][1] !== -1) {
            return $this->wrapInline('bold', $matches[9][0], $pos);
        }

        // Group 10: Italic
        if (isset($matches[10]) && $matches[10][1] !== -1) {
            return $this->wrapInline('italic', $matches[10][0], $pos);
        }

        // Group 11: Underline
        if (isset($matches[11]) && $matches[11][1] !== -1) {
            return $this->wrapInline('underline', $matches[11][0], $pos);
        }

        // Group 12: Monospace
        if (isset($matches[12]) && $matches[12][1] !== -1) {
            return $this->wrapInline('tt', $matches[12][0], $pos);
        }

        // Group 13: Superscript
        if (isset($matches[13]) && $matches[13][1] !== -1) {
            return $this->wrapInline('superscript', $matches[13][0], $pos);
        }

        // Group 14: Subscript
        if (isset($matches[14]) && $matches[14][1] !== -1) {
            return $this->wrapInline('subscript', $matches[14][0], $pos);
        }

        // Group 15,16: Colortext
        if (isset($matches[15]) && $matches[15][1] !== -1) {
            $color = $matches[15][0];
            $text = $matches[16][0];
            return [
                new Token(TokenType::OPEN_TAG, 'colortext', $pos, ['color' => $color]),
                new Token(TokenType::TEXT, $text, $pos),
                new Token(TokenType::CLOSE_TAG, 'colortext', $pos),
            ];
        }

        // Group 17: Break
        if (str_contains($matches[0][0], " _\n")) {
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
     * Tokenize a described URL [href|text]
     *
     * The href might be a URL or a wiki page name
     */
    private function tokenizeDescribedUrl(string $href, string $text, int $pos): array
    {
        $isUrl = false;
        foreach (self::URL_SCHEMES as $scheme) {
            if (str_starts_with($href, $scheme)) {
                $isUrl = true;
                break;
            }
        }

        if ($isUrl) {
            return [
                new Token(TokenType::OPEN_TAG, 'url', $pos, ['href' => $href]),
                new Token(TokenType::TEXT, $text, $pos),
                new Token(TokenType::CLOSE_TAG, 'url', $pos),
            ];
        }

        // Treat as wikilink
        return $this->tokenizeWikilink($href, $text, $pos);
    }

    /**
     * Tokenize a wikilink — split target on # for anchor
     */
    private function tokenizeWikilink(string $target, string $text, int $pos): array
    {
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
     * Tokenize code block: {CODE(attrs)}\n...\n{CODE}
     *
     * @return array{tokens: array<Token>, end: int}
     */
    private function tokenizeCode(array $matches, int $pos): array
    {
        $attrString = $matches[1];
        $content = $matches[2];
        $end = $pos + strlen($matches[0]);

        $attrs = [];
        if ($attrString !== '' && preg_match('/\blang(?:uage)?\s*=\s*"?([^",\s]+)"?/i', $attrString, $m)) {
            $attrs['language'] = $m[1];
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
     * Tokenize raw block: ~np~...~/np~
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
     * Tokenize TOC: {toc} or {maketoc}
     *
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
     * Tokenize image: {img src="url" alt="text" ...}
     *
     * @return array{tokens: array<Token>, end: int}
     */
    private function tokenizeImage(array $matches, int $pos): array
    {
        $attrString = $matches[1];
        $end = $pos + strlen($matches[0]);

        $attrs = [];
        // Parse key="value" or key=value pairs
        if (preg_match_all('/(\w+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|(\S+))/', $attrString, $attrMatches, PREG_SET_ORDER)) {
            foreach ($attrMatches as $m) {
                $key = strtolower($m[1]);
                $value = $m[2] !== '' ? $m[2] : ($m[3] !== '' ? $m[3] : $m[4]);
                if (in_array($key, ['src', 'alt', 'width', 'height', 'align', 'desc', 'link'], true)) {
                    $attrs[$key] = $value;
                }
            }
        }

        return [
            'tokens' => [
                new Token(TokenType::OPEN_TAG, 'image', $pos, $attrs),
            ],
            'end' => $end,
        ];
    }

    /**
     * Tokenize heading: ! to !!!!!! followed by text
     *
     * @return array{tokens: array<Token>, end: int}
     */
    private function tokenizeHeading(array $matches, int $pos): array
    {
        $level = strlen($matches[1]);
        $text = trim($matches[2]);
        $end = $pos + strlen($matches[0]);

        // Strip optional collapse marker (-/+) after exclamation marks
        $text = preg_replace('/^[-+]\s*/', '', $text);

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
     * Tokenize list block — Tiki uses character repetition for nesting
     *
     * * item (level 1 bullet)
     * ** item (level 2 bullet)
     * # item (level 1 numbered)
     * ## item (level 2 numbered)
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
            if (preg_match('/^([\*#]+)\s*(.*)$/', $line, $m)) {
                $markers = $m[1];
                $level = strlen($markers);
                $type = ($markers[strlen($markers) - 1] === '#') ? 'number' : 'bullet';
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
     * Tokenize table: ||cell1|cell2||
     *
     * Outer || marks row boundaries, inner | separates cells.
     * Cell prefixes: ~ header, > right align, = center, < left align
     *
     * @return array{tokens: array<Token>, end: int}
     */
    private function tokenizeTable(array $matches, int $pos): array
    {
        $content = $matches[1];
        $end = $pos + strlen($matches[0]);
        $tokens = [];

        $tokens[] = new Token(TokenType::OPEN_TAG, 'table', $pos);

        // Split rows by newline
        $rows = preg_split('/\n/', trim($content));

        foreach ($rows as $rowContent) {
            $rowContent = trim($rowContent);
            if ($rowContent === '') {
                continue;
            }

            $tokens[] = new Token(TokenType::OPEN_TAG, 'row', $pos);

            // Split cells by single pipe (not double)
            $cells = explode('|', $rowContent);

            foreach ($cells as $cellContent) {
                $cellContent = trim($cellContent);
                if ($cellContent === '') {
                    continue;
                }

                $cellAttrs = ['type' => 'data'];

                // Check cell prefix markers
                if (str_starts_with($cellContent, '~')) {
                    $cellAttrs['type'] = 'header';
                    $cellContent = ltrim(substr($cellContent, 1));
                } elseif (str_starts_with($cellContent, '>')) {
                    $cellAttrs['align'] = 'right';
                    $cellContent = ltrim(substr($cellContent, 1));
                } elseif (str_starts_with($cellContent, '=')) {
                    $cellAttrs['align'] = 'center';
                    $cellContent = ltrim(substr($cellContent, 1));
                } elseif (str_starts_with($cellContent, '<')) {
                    $cellAttrs['align'] = 'left';
                    $cellContent = ltrim(substr($cellContent, 1));
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
     * Tokenize center: ::text::
     *
     * @return array{tokens: array<Token>, end: int}
     */
    private function tokenizeCenter(array $matches, int $pos): array
    {
        $content = $matches[1];
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
            if (preg_match('/^;(.+?):(.*)$/', $line, $m)) {
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
