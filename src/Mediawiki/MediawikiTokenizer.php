<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Mediawiki;

use Horde\Text\Wiki\Token;
use Horde\Text\Wiki\TokenType;
use Horde\Text\Wiki\Tokenizer;

/**
 * MediaWiki tokenizer
 *
 * Converts MediaWiki wikitext into a token stream.
 *
 * MediaWiki syntax:
 * - '''bold''' (apostrophe state machine)
 * - ''italic''
 * - Headings: = H1 = to ====== H6 ====== (NOT inverted)
 * - Links: [[Page|text]], [http://url text], bare URLs
 * - Images: [[File:img|alt]] or [[Image:img|alt]]
 * - Email: [[mailto:user@example.com|text]]
 * - Lists: * bullet, # numbered (character repetition nesting)
 * - Tables: {| |- | ! |}
 * - Code: <code>...</code>
 * - Pre: <pre>...</pre>
 * - Nowiki: <nowiki>...</nowiki>
 * - HTML subset: <sup>, <sub>, <u>, <tt>, <s>, <del>, <ins>, <br />, <blockquote>
 * - Alignment: <div style="text-align:center|left|right|justify">
 * - Styling: <span style="color:X">, <span style="font-family:X">, <span style="font-size:X">
 * - Anchors: <span id="name"></span>
 * - TOC: __TOC__, __NOTOC__, __FORCETOC__
 * - Horizontal rule: ----
 * - Definition lists: ; term / : definition
 * - YouTube: configurable well-known URL domains
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class MediawikiTokenizer implements Tokenizer
{
    /** @var array<array{pattern: string, handler: string}> */
    private array $blockPatterns;

    private string $inlinePattern;

    private const URL_SCHEMES = [
        'http://', 'https://', 'ftp://', 'gopher://', 'news://', 'file://', 'mailto:',
    ];

    private const IMAGE_PREFIXES = ['File', 'file', 'Image', 'image'];

    /** @var array<string> YouTube domain list for well-known URL detection */
    private array $youtubeDomains;

    public function __construct(array $options = [])
    {
        $this->youtubeDomains = $options['youtubeDomains'] ?? [
            'youtube.com', 'www.youtube.com', 'youtu.be',
            'm.youtube.com',
        ];
        $this->buildPatterns();
    }

    /**
     * Tokenize MediaWiki markup
     *
     * @param string $text Source text
     *
     * @return iterable<Token> Token stream
     */
    public function tokenize(string $text): iterable
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Strip HTML comments
        $text = preg_replace('/<!--.*?-->/s', '', $text);

        $pos = 0;
        $length = strlen($text);

        while ($pos < $length) {
            $atLineStart = ($pos === 0 || $text[$pos - 1] === "\n");

            $blockMatch = null;
            $inlineMatch = null;

            if ($atLineStart) {
                $blockMatch = $this->matchBlock($text, $pos);
            }

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
                yield from $this->emitTextWithEmphasis($chunk, $pos);
                $pos = $nextPos;
            } elseif ($inlineMatch !== null) {
                $chunk = substr($text, $pos, $inlineMatch['start'] - $pos);
                if ($chunk !== '') {
                    yield from $this->emitTextWithEmphasis($chunk, $pos);
                }
                foreach ($inlineMatch['tokens'] as $token) {
                    yield $token;
                }
                $pos = $inlineMatch['end'];
            } else {
                $chunk = substr($text, $pos);
                yield from $this->emitTextWithEmphasis($chunk, $pos);
                break;
            }
        }
    }

    // ---------------------------------------------------------------
    // Pattern building
    // ---------------------------------------------------------------

    private function buildPatterns(): void
    {
        $this->blockPatterns = [
            // Code block: <code>...</code>
            ['pattern' => '~\G<code(?:\s+([^>]*))?>(?:<pre>)?\n?(.*?)(?:</pre>)?\n?</code>(?:\n|$)~si', 'handler' => 'tokenizeCode'],
            // Pre block: <pre>...</pre>
            ['pattern' => '~\G<pre[^>]*>\n?(.*?)\n?</pre>(?:\n|$)~si', 'handler' => 'tokenizePreformatted'],
            // Nowiki block: <nowiki>...</nowiki>
            ['pattern' => '~\G<nowiki>\n?(.*?)\n?</nowiki>(?:\n|$)~si', 'handler' => 'tokenizeRaw'],
            // Table: {| ... |}
            ['pattern' => '~\G\{\|[^\n]*\n((?:(?!\|\})[\s\S])*?)\|\}(?:\n|$)~', 'handler' => 'tokenizeTable'],
            // Heading: = to ====== with matching closing markers
            ['pattern' => '~\G^(={1,6})(.*?)\1\s*$(?:\n|$)~m', 'handler' => 'tokenizeHeading'],
            // Horizontal rule: four or more dashes
            ['pattern' => '~\G^-{4,}\s*$(?:\n|$)~m', 'handler' => 'tokenizeHoriz'],
            // List: * or # character repetition
            ['pattern' => '~\G(^[\*#]+.+(?:\n|$))+~m', 'handler' => 'tokenizeList'],
            // Definition list: ; or : prefixed lines
            ['pattern' => '~\G(^[;:].+(?:\n|$))+~m', 'handler' => 'tokenizeDeflist'],
            // Blockquote: <blockquote>...</blockquote>
            ['pattern' => '~\G<blockquote>(.*?)</blockquote>(?:\n|$)~si', 'handler' => 'tokenizeBlockquote'],
            // Div text-align: center/left/right/justify
            ['pattern' => '~\G<div\s+style\s*=\s*"text-align:\s*(center|left|right|justify)\s*;?\s*">(.*?)</div>(?:\n|$)~si', 'handler' => 'tokenizeAlignment'],
            // Space-indented preformatted: lines starting with single space
            ['pattern' => '~\G(^ .+(?:\n|$))+~m', 'handler' => 'tokenizeSpacePreformatted'],
            // TOC magic words
            ['pattern' => '~\G^__(TOC|NOTOC|FORCETOC)__\s*$(?:\n|$)~m', 'handler' => 'tokenizeToc'],
        ];

        // Inline patterns — combined alternation using ~ delimiter
        $schemes = implode('|', array_map(
            fn($s) => preg_quote($s, '~'),
            self::URL_SCHEMES
        ));

        $this->inlinePattern = '~'
            . '(?:'
            // 1,2: Wikilink/Image/Email: [[target|text]]
            . '\[\[([^\]]+?)(?:\|([^\]]*))?\]\]'
            . '|'
            // 3,4: Described external URL: [http://url text]
            . '\[((?:' . $schemes . ')[^\]\s]+)\s+([^\]]+)\]'
            . '|'
            // 5: Bare URL (non-mailto)
            . '((?:https?://|ftp://)[^\s"\'<>\[\]]+)'
            . '|'
            // 6,7: Span style color
            . '<span\s+style\s*=\s*"color:\s*([^"]+?)\s*;?\s*">(.*?)</span>'
            . '|'
            // 8,9: Span style font-family
            . '<span\s+style\s*=\s*"font-family:\s*([^"]+?)\s*;?\s*">(.*?)</span>'
            . '|'
            // 10,11: Span style font-size
            . '<span\s+style\s*=\s*"font-size:\s*([^"]+?)\s*;?\s*">(.*?)</span>'
            . '|'
            // 12: Span id anchor
            . '<span\s+id\s*=\s*"([^"]+)"\s*></span>'
            . '|'
            // 13,14: HTML inline tags: sup, sub, u, tt, s, del, ins
            . '<(sup|sub|u|tt|s|del|ins)>(.*?)</\13>'
            . '|'
            // 15: Break: <br> / <br /> / <br/>
            . '(<br\s*/?>)'
            . '|'
            // 16: Inline nowiki: <nowiki>...</nowiki>
            . '<nowiki>(.*?)</nowiki>'
            . ')~si';
    }

    // ---------------------------------------------------------------
    // Block matching
    // ---------------------------------------------------------------

    /** @return array{tokens: array<Token>, end: int}|null */
    private function matchBlock(string $text, int $pos): ?array
    {
        foreach ($this->blockPatterns as $entry) {
            if (preg_match($entry['pattern'], $text, $matches, 0, $pos)) {
                $handler = $entry['handler'];
                $result = $this->$handler($matches, $pos);
                return $result;
            }
        }
        return null;
    }

    // ---------------------------------------------------------------
    // Inline matching
    // ---------------------------------------------------------------

    /** @return array{tokens: array<Token>, start: int, end: int}|null */
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

    /** @return array<Token> */
    private function tokenizeInlineMatch(array $matches, int $pos): array
    {
        // 1,2: Wikilink/Image/Email: [[target|text]]
        if (isset($matches[1]) && $matches[1][1] !== -1) {
            $target = $matches[1][0];
            $text = (isset($matches[2]) && $matches[2][1] !== -1) ? $matches[2][0] : '';
            return $this->tokenizeWikilink($target, $text, $pos);
        }

        // 3,4: Described external URL: [url text]
        if (isset($matches[3]) && $matches[3][1] !== -1) {
            $href = $matches[3][0];
            $text = $matches[4][0];
            return [
                new Token(TokenType::OPEN_TAG, 'url', $pos, ['href' => $href]),
                new Token(TokenType::TEXT, $text, $pos),
                new Token(TokenType::CLOSE_TAG, 'url', $pos),
            ];
        }

        // 5: Bare URL
        if (isset($matches[5]) && $matches[5][1] !== -1) {
            $href = $matches[5][0];
            return $this->tokenizeBareUrl($href, $pos);
        }

        // 6,7: Span style color
        if (isset($matches[6]) && $matches[6][1] !== -1) {
            $color = $matches[6][0];
            $content = $matches[7][0];
            return [
                new Token(TokenType::OPEN_TAG, 'color', $pos, ['color' => $color]),
                new Token(TokenType::TEXT, $content, $pos),
                new Token(TokenType::CLOSE_TAG, 'color', $pos),
            ];
        }

        // 8,9: Span style font-family
        if (isset($matches[8]) && $matches[8][1] !== -1) {
            $font = $matches[8][0];
            $content = $matches[9][0];
            return [
                new Token(TokenType::OPEN_TAG, 'font', $pos, ['font' => $font]),
                new Token(TokenType::TEXT, $content, $pos),
                new Token(TokenType::CLOSE_TAG, 'font', $pos),
            ];
        }

        // 10,11: Span style font-size
        if (isset($matches[10]) && $matches[10][1] !== -1) {
            $size = $matches[10][0];
            return [
                new Token(TokenType::OPEN_TAG, 'size', $pos, ['size' => $size]),
                new Token(TokenType::TEXT, $matches[11][0], $pos),
                new Token(TokenType::CLOSE_TAG, 'size', $pos),
            ];
        }

        // 12: Span id anchor
        if (isset($matches[12]) && $matches[12][1] !== -1) {
            return [
                new Token(TokenType::OPEN_TAG, 'anchor', $pos, ['name' => $matches[12][0]]),
            ];
        }

        // 13,14: HTML inline tags
        if (isset($matches[13]) && $matches[13][1] !== -1) {
            $tagMap = [
                'sup' => 'superscript', 'sub' => 'subscript',
                'u' => 'underline', 'tt' => 'tt',
                's' => 'strike', 'del' => 'del', 'ins' => 'ins',
            ];
            $htmlTag = strtolower($matches[13][0]);
            $astTag = $tagMap[$htmlTag] ?? $htmlTag;
            return $this->wrapInline($astTag, $matches[14][0], $pos);
        }

        // 15: Break
        if (isset($matches[15]) && $matches[15][1] !== -1) {
            return [
                new Token(TokenType::OPEN_TAG, 'break', $pos),
            ];
        }

        // 16: Inline nowiki
        if (isset($matches[16]) && $matches[16][1] !== -1) {
            return [
                new Token(TokenType::TEXT, $matches[16][0], $pos),
            ];
        }

        return [];
    }

    // ---------------------------------------------------------------
    // Wikilink / Image / Email disambiguation
    // ---------------------------------------------------------------

    /** @return array<Token> */
    private function tokenizeWikilink(string $target, string $text, int $pos): array
    {
        // mailto: → email tag
        if (str_starts_with(strtolower($target), 'mailto:')) {
            $email = substr($target, 7); // strip "mailto:"
            $displayText = ($text !== '') ? $text : $email;
            return [
                new Token(TokenType::OPEN_TAG, 'email', $pos, ['email' => $email]),
                new Token(TokenType::TEXT, $displayText, $pos),
                new Token(TokenType::CLOSE_TAG, 'email', $pos),
            ];
        }

        // Check for Image:/File: prefix
        foreach (self::IMAGE_PREFIXES as $prefix) {
            if (str_starts_with($target, $prefix . ':')) {
                $src = substr($target, strlen($prefix) + 1);
                $attrs = ['src' => $src];
                if ($text !== '') {
                    $attrs['alt'] = $text;
                }
                return [
                    new Token(TokenType::OPEN_TAG, 'image', $pos, $attrs),
                ];
            }
        }

        // URL scheme → external URL
        foreach (self::URL_SCHEMES as $scheme) {
            if (str_starts_with($target, $scheme)) {
                $displayText = ($text !== '') ? $text : $target;
                return [
                    new Token(TokenType::OPEN_TAG, 'url', $pos, ['href' => $target]),
                    new Token(TokenType::TEXT, $displayText, $pos),
                    new Token(TokenType::CLOSE_TAG, 'url', $pos),
                ];
            }
        }

        // Wikilink — split on # for anchor
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

    /**
     * Tokenize a bare URL — check for YouTube well-known domains
     *
     * @return array<Token>
     */
    private function tokenizeBareUrl(string $href, int $pos): array
    {
        // Check for YouTube
        $videoId = $this->extractYoutubeId($href);
        if ($videoId !== null) {
            return [
                new Token(TokenType::OPEN_TAG, 'youtube', $pos),
                new Token(TokenType::TEXT, $videoId, $pos),
                new Token(TokenType::CLOSE_TAG, 'youtube', $pos),
            ];
        }

        return [
            new Token(TokenType::OPEN_TAG, 'url', $pos, ['href' => $href]),
            new Token(TokenType::TEXT, $href, $pos),
            new Token(TokenType::CLOSE_TAG, 'url', $pos),
        ];
    }

    /**
     * Extract YouTube video ID from a URL
     *
     * Supports: youtube.com/watch?v=ID, youtu.be/ID, youtube.com/embed/ID
     */
    private function extractYoutubeId(string $url): ?string
    {
        $parsed = parse_url($url);
        if ($parsed === false || !isset($parsed['host'])) {
            return null;
        }

        $host = strtolower($parsed['host']);
        $isYoutube = false;
        foreach ($this->youtubeDomains as $domain) {
            if ($host === $domain) {
                $isYoutube = true;
                break;
            }
        }

        if (!$isYoutube) {
            return null;
        }

        // youtu.be/ID
        if ($host === 'youtu.be') {
            $path = $parsed['path'] ?? '';
            $id = ltrim($path, '/');
            if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $id)) {
                return $id;
            }
            return null;
        }

        // youtube.com/watch?v=ID
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $query);
            if (isset($query['v']) && preg_match('/^[a-zA-Z0-9_-]{11}$/', $query['v'])) {
                return $query['v'];
            }
        }

        // youtube.com/embed/ID
        $path = $parsed['path'] ?? '';
        if (preg_match('~/embed/([a-zA-Z0-9_-]{11})~', $path, $m)) {
            return $m[1];
        }

        return null;
    }

    // ---------------------------------------------------------------
    // Block tokenizers
    // ---------------------------------------------------------------

    /** @return array{tokens: array<Token>, end: int} */
    private function tokenizeCode(array $matches, int $pos): array
    {
        $content = $matches[2];
        $end = $pos + strlen($matches[0]);
        $attrs = [];

        if (isset($matches[1]) && trim($matches[1]) !== '') {
            $attrs['language'] = trim($matches[1]);
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

    /** @return array{tokens: array<Token>, end: int} */
    private function tokenizePreformatted(array $matches, int $pos): array
    {
        $content = $matches[1];
        $end = $pos + strlen($matches[0]);

        return [
            'tokens' => [
                new Token(TokenType::OPEN_TAG, 'preformatted', $pos),
                new Token(TokenType::TEXT, $content, $pos),
                new Token(TokenType::CLOSE_TAG, 'preformatted', $pos),
            ],
            'end' => $end,
        ];
    }

    /** @return array{tokens: array<Token>, end: int} */
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
     * Tokenize MediaWiki table: {| ... |}
     *
     * Structure:
     *   {| attributes
     *   |+ caption (optional, deferred)
     *   |- row attributes
     *   ! header1 !! header2
     *   | cell1 || cell2
     *   |}
     *
     * @return array{tokens: array<Token>, end: int}
     */
    private function tokenizeTable(array $matches, int $pos): array
    {
        $body = $matches[1];
        $end = $pos + strlen($matches[0]);
        $tokens = [];
        $tokens[] = new Token(TokenType::OPEN_TAG, 'table', $pos);

        // Split body into rows by |- separator
        $rowTexts = preg_split('/^\|\-[^\n]*$/m', $body);

        foreach ($rowTexts as $rowText) {
            $rowText = trim($rowText);
            if ($rowText === '') {
                continue;
            }

            // Skip caption lines (|+ ...)
            $lines = explode("\n", $rowText);
            $cellLines = [];
            foreach ($lines as $line) {
                $trimmed = ltrim($line);
                if (str_starts_with($trimmed, '|+')) {
                    continue;
                }
                if ($trimmed !== '') {
                    $cellLines[] = $trimmed;
                }
            }

            if (empty($cellLines)) {
                continue;
            }

            $tokens[] = new Token(TokenType::OPEN_TAG, 'row', $pos);

            foreach ($cellLines as $cellLine) {
                if (str_starts_with($cellLine, '!')) {
                    // Header cells: ! cell !! cell
                    $content = substr($cellLine, 1);
                    $parts = preg_split('/!!/', $content);
                    foreach ($parts as $part) {
                        $cellContent = $this->extractCellContent(trim($part));
                        $tokens[] = new Token(TokenType::OPEN_TAG, 'cell', $pos, ['type' => 'header']);
                        $tokens[] = new Token(TokenType::TEXT, $cellContent, $pos);
                        $tokens[] = new Token(TokenType::CLOSE_TAG, 'cell', $pos);
                    }
                } elseif (str_starts_with($cellLine, '|')) {
                    // Data cells: | cell || cell
                    $content = substr($cellLine, 1);
                    $parts = preg_split('/\|\|/', $content);
                    foreach ($parts as $part) {
                        $cellContent = $this->extractCellContent(trim($part));
                        $tokens[] = new Token(TokenType::OPEN_TAG, 'cell', $pos, ['type' => 'data']);
                        $tokens[] = new Token(TokenType::TEXT, $cellContent, $pos);
                        $tokens[] = new Token(TokenType::CLOSE_TAG, 'cell', $pos);
                    }
                }
            }

            $tokens[] = new Token(TokenType::CLOSE_TAG, 'row', $pos);
        }

        $tokens[] = new Token(TokenType::CLOSE_TAG, 'table', $pos);

        return ['tokens' => $tokens, 'end' => $end];
    }

    /**
     * Extract cell content, stripping optional cell attributes
     *
     * MediaWiki allows: attributes | content
     * If there's a single pipe, attributes come before and content after.
     */
    private function extractCellContent(string $raw): string
    {
        // If there's a single | that is not ||, the left side is attributes
        if (str_contains($raw, '|') && !str_contains($raw, '||')) {
            $parts = explode('|', $raw, 2);
            return trim($parts[1]);
        }
        return $raw;
    }

    /**
     * Tokenize heading: = H1 = to ====== H6 ======
     *
     * MediaWiki headings are NOT inverted: = is H1, == is H2, etc.
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

    /** @return array{tokens: array<Token>, end: int} */
    private function tokenizeHoriz(array $matches, int $pos): array
    {
        return [
            'tokens' => [
                new Token(TokenType::OPEN_TAG, 'horiz', $pos),
            ],
            'end' => $pos + strlen($matches[0]),
        ];
    }

    /**
     * Tokenize list: character-repetition nesting
     *
     * * = bullet level 1, ** = bullet level 2
     * # = number level 1, ## = number level 2
     * *# = numbered inside bullet (mixed nesting)
     *
     * @return array{tokens: array<Token>, end: int}
     */
    private function tokenizeList(array $matches, int $pos): array
    {
        $block = $matches[0];
        $end = $pos + strlen($block);
        $lines = explode("\n", rtrim($block, "\n"));
        $tokens = [];

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
     * Tokenize definition list
     *
     * MediaWiki format:
     *   ; term
     *   : definition
     * Or inline: ; term : definition
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

        $i = 0;
        $count = count($lines);
        while ($i < $count) {
            $line = $lines[$i];

            if (str_starts_with($line, ';')) {
                $termContent = ltrim(substr($line, 1));

                // Check for inline definition: ; term : definition
                if (str_contains($termContent, ' : ')) {
                    [$term, $def] = explode(' : ', $termContent, 2);
                    $tokens[] = new Token(TokenType::OPEN_TAG, 'defterm', $pos);
                    $tokens[] = new Token(TokenType::TEXT, trim($term), $pos);
                    $tokens[] = new Token(TokenType::CLOSE_TAG, 'defterm', $pos);
                    $tokens[] = new Token(TokenType::OPEN_TAG, 'defdef', $pos);
                    $tokens[] = new Token(TokenType::TEXT, trim($def), $pos);
                    $tokens[] = new Token(TokenType::CLOSE_TAG, 'defdef', $pos);
                } else {
                    $tokens[] = new Token(TokenType::OPEN_TAG, 'defterm', $pos);
                    $tokens[] = new Token(TokenType::TEXT, trim($termContent), $pos);
                    $tokens[] = new Token(TokenType::CLOSE_TAG, 'defterm', $pos);

                    // Check if next line is : definition
                    if ($i + 1 < $count && str_starts_with($lines[$i + 1], ':')) {
                        $i++;
                        $defContent = ltrim(substr($lines[$i], 1));
                        $tokens[] = new Token(TokenType::OPEN_TAG, 'defdef', $pos);
                        $tokens[] = new Token(TokenType::TEXT, trim($defContent), $pos);
                        $tokens[] = new Token(TokenType::CLOSE_TAG, 'defdef', $pos);
                    }
                }
            } elseif (str_starts_with($line, ':')) {
                // Standalone definition (no preceding term)
                $defContent = ltrim(substr($line, 1));
                $tokens[] = new Token(TokenType::OPEN_TAG, 'defdef', $pos);
                $tokens[] = new Token(TokenType::TEXT, trim($defContent), $pos);
                $tokens[] = new Token(TokenType::CLOSE_TAG, 'defdef', $pos);
            }

            $i++;
        }

        $tokens[] = new Token(TokenType::CLOSE_TAG, 'deflist', $pos);

        return ['tokens' => $tokens, 'end' => $end];
    }

    /** @return array{tokens: array<Token>, end: int} */
    private function tokenizeBlockquote(array $matches, int $pos): array
    {
        $content = trim($matches[1]);
        $end = $pos + strlen($matches[0]);

        return [
            'tokens' => [
                new Token(TokenType::OPEN_TAG, 'blockquote', $pos),
                new Token(TokenType::TEXT, $content, $pos),
                new Token(TokenType::CLOSE_TAG, 'blockquote', $pos),
            ],
            'end' => $end,
        ];
    }

    /**
     * Tokenize div text-align: center/left/right/justify
     *
     * @return array{tokens: array<Token>, end: int}
     */
    private function tokenizeAlignment(array $matches, int $pos): array
    {
        $alignment = strtolower($matches[1]);
        $content = trim($matches[2]);
        $end = $pos + strlen($matches[0]);

        return [
            'tokens' => [
                new Token(TokenType::OPEN_TAG, $alignment, $pos),
                new Token(TokenType::TEXT, $content, $pos),
                new Token(TokenType::CLOSE_TAG, $alignment, $pos),
            ],
            'end' => $end,
        ];
    }

    /**
     * Tokenize space-indented preformatted text
     *
     * Lines starting with a space in MediaWiki are preformatted.
     *
     * @return array{tokens: array<Token>, end: int}
     */
    private function tokenizeSpacePreformatted(array $matches, int $pos): array
    {
        $block = $matches[0];
        $end = $pos + strlen($block);

        // Strip leading space from each line
        $lines = explode("\n", rtrim($block, "\n"));
        $content = implode("\n", array_map(fn($l) => substr($l, 1), $lines));

        return [
            'tokens' => [
                new Token(TokenType::OPEN_TAG, 'preformatted', $pos),
                new Token(TokenType::TEXT, $content, $pos),
                new Token(TokenType::CLOSE_TAG, 'preformatted', $pos),
            ],
            'end' => $end,
        ];
    }

    /** @return array{tokens: array<Token>, end: int} */
    private function tokenizeToc(array $matches, int $pos): array
    {
        return [
            'tokens' => [
                new Token(TokenType::OPEN_TAG, 'toc', $pos),
            ],
            'end' => $pos + strlen($matches[0]),
        ];
    }

    // ---------------------------------------------------------------
    // Apostrophe state machine (Bold/Italic)
    // ---------------------------------------------------------------

    /**
     * Emit text tokens with inline emphasis processing
     *
     * Processes '''bold''' and ''italic'' apostrophe sequences
     * within plain text chunks.
     *
     * @return iterable<Token>
     */
    private function emitTextWithEmphasis(string $text, int $pos): iterable
    {
        // Quick check: if no consecutive apostrophes, emit as plain text
        if (!str_contains($text, "''")) {
            yield from $this->textTokens($text, $pos);
            return;
        }

        // Split on apostrophe sequences
        $parts = preg_split("/('{2,5})/", $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        $boldOpen = false;
        $italicOpen = false;

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            $aposLen = 0;
            if (preg_match("/^'{2,5}$/", $part)) {
                $aposLen = strlen($part);
            }

            if ($aposLen === 0) {
                yield from $this->textTokens($part, $pos);
                $pos += strlen($part);
                continue;
            }

            // Normalize: 4 apostrophes = 1 literal + 3 bold
            if ($aposLen === 4) {
                yield new Token(TokenType::TEXT, "'", $pos);
                $pos += 1;
                $aposLen = 3;
            }

            if ($aposLen === 5) {
                // Toggle both bold and italic
                if ($boldOpen) {
                    yield new Token(TokenType::CLOSE_TAG, 'strong', $pos);
                } else {
                    yield new Token(TokenType::OPEN_TAG, 'strong', $pos);
                }
                $boldOpen = !$boldOpen;

                if ($italicOpen) {
                    yield new Token(TokenType::CLOSE_TAG, 'emphasis', $pos);
                } else {
                    yield new Token(TokenType::OPEN_TAG, 'emphasis', $pos);
                }
                $italicOpen = !$italicOpen;
                $pos += 5;
            } elseif ($aposLen === 3) {
                if ($boldOpen) {
                    yield new Token(TokenType::CLOSE_TAG, 'strong', $pos);
                } else {
                    yield new Token(TokenType::OPEN_TAG, 'strong', $pos);
                }
                $boldOpen = !$boldOpen;
                $pos += 3;
            } elseif ($aposLen === 2) {
                if ($italicOpen) {
                    yield new Token(TokenType::CLOSE_TAG, 'emphasis', $pos);
                } else {
                    yield new Token(TokenType::OPEN_TAG, 'emphasis', $pos);
                }
                $italicOpen = !$italicOpen;
                $pos += 2;
            }
        }

        // Auto-close any unclosed emphasis
        if ($italicOpen) {
            yield new Token(TokenType::CLOSE_TAG, 'emphasis', $pos);
        }
        if ($boldOpen) {
            yield new Token(TokenType::CLOSE_TAG, 'strong', $pos);
        }
    }

    // ---------------------------------------------------------------
    // Helper methods
    // ---------------------------------------------------------------

    /** @return array<Token> */
    private function wrapInline(string $tagName, string $content, int $pos): array
    {
        return [
            new Token(TokenType::OPEN_TAG, $tagName, $pos),
            new Token(TokenType::TEXT, $content, $pos),
            new Token(TokenType::CLOSE_TAG, $tagName, $pos),
        ];
    }

    /** @return iterable<Token> */
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

    private function nextLineStart(string $text, int $pos): ?int
    {
        $nlPos = strpos($text, "\n", $pos);
        return ($nlPos !== false) ? $nlPos + 1 : null;
    }
}
