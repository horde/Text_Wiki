<?php

declare(strict_types=1);

/**
 * Copyright 2025-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Commonmark;

use Horde\Text\Wiki\Node\ElementNode;
use Horde\Text\Wiki\Node\Node;
use Horde\Text\Wiki\Node\TextNode;
use Horde\Text\Wiki\TagRegistry;

/**
 * CommonMark inline parser — delimiter-stack-based inline parsing
 *
 * Implements phase 2 of the CommonMark parsing algorithm:
 * parses inline content (emphasis, links, code spans, etc.)
 * within paragraph and heading text.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class InlineParser
{
    private LinkReferenceMap $refMap;
    private TagRegistry $registry;

    public function __construct(LinkReferenceMap $refMap, TagRegistry $registry)
    {
        $this->refMap = $refMap;
        $this->registry = $registry;
    }

    /**
     * Parse inline content and return nodes
     *
     * @return list<Node>
     */
    public function parse(string $text): array
    {
        $inlines = [];
        $delimStack = new DelimiterStack();
        $pos = 0;
        $len = strlen($text);
        $textBuf = '';

        while ($pos < $len) {
            $char = $text[$pos];

            $result = match ($char) {
                '`' => $this->parseCodeSpan($text, $pos),
                '\\' => $this->parseBackslash($text, $pos, $len),
                '*', '_' => $this->parseEmphasisDelimiter($text, $pos, $len, $char, $inlines, $delimStack, $textBuf),
                '[' => $this->parseLinkOpen($text, $pos, $len, $inlines, $delimStack, $textBuf),
                '!' => $this->parseImageOpen($text, $pos, $len, $inlines, $delimStack, $textBuf),
                ']' => $this->parseLinkClose($text, $pos, $len, $inlines, $delimStack, $textBuf),
                '<' => $this->parseAutolink($text, $pos, $len),
                '&' => $this->parseEntity($text, $pos, $len),
                '~' => $this->parseStrikethrough($text, $pos, $len, $inlines, $delimStack, $textBuf),
                "\n" => $this->parseNewline($text, $pos, $len, $textBuf),
                'w', 'W', 'h', 'H', 'f', 'F' => $this->parseExtendedAutolink($text, $pos, $len)
                    ?? $this->parseExtendedAutolinkEmail($text, $pos, $len),
                default => $this->parseExtendedAutolinkEmail($text, $pos, $len),
            };

            if ($result !== null) {
                // Flush text buffer
                if ($textBuf !== '') {
                    $inlines[] = new TextNode($textBuf);
                    $textBuf = '';
                }

                if ($result instanceof Node) {
                    $inlines[] = $result;
                } elseif (is_array($result)) {
                    // Multiple nodes (e.g., from emphasis delimiter handling)
                    foreach ($result as $node) {
                        $inlines[] = $node;
                    }
                } elseif ($result === true) {
                    // Handled internally (delimiter added to stack, etc.)
                }

                $pos = $this->lastPos;
            } else {
                $textBuf .= $char;
                $pos++;
            }
        }

        // Flush remaining text
        if ($textBuf !== '') {
            $inlines[] = new TextNode($textBuf);
        }

        // Process emphasis
        $delimStack->processEmphasis($inlines);

        return $inlines;
    }

    /** Updated by parse handlers to indicate where parsing should resume */
    private int $lastPos = 0;

    // ---------------------------------------------------------------
    // Code span
    // ---------------------------------------------------------------

    private function parseCodeSpan(string $text, int $pos): ?Node
    {
        $len = strlen($text);

        // Count opening backticks
        $start = $pos;
        while ($pos < $len && $text[$pos] === '`') {
            $pos++;
        }
        $tickCount = $pos - $start;

        // Find matching closing backticks
        $searchPos = $pos;
        while ($searchPos < $len) {
            $closePos = strpos($text, str_repeat('`', $tickCount), $searchPos);
            if ($closePos === false) {
                break;
            }

            // Check that closing ticks are exactly $tickCount (not more)
            // Check character before the match isn't a backtick
            if ($closePos > 0 && $text[$closePos - 1] === '`') {
                $searchPos = $closePos + 1;
                continue;
            }
            $endOfClose = $closePos + $tickCount;
            if ($endOfClose < $len && $text[$endOfClose] === '`') {
                $searchPos = $closePos + 1;
                continue;
            }

            // Found matching closing backticks
            $content = substr($text, $pos, $closePos - $pos);

            // Collapse internal whitespace: strip one leading and trailing space
            // if the content has at least one non-space character
            $content = preg_replace('/\n/', ' ', $content) ?? $content;
            if (strlen($content) >= 2
                && $content[0] === ' '
                && $content[strlen($content) - 1] === ' '
                && trim($content) !== ''
            ) {
                $content = substr($content, 1, -1);
            }

            $node = new ElementNode('tt');
            $node->setVerbatim(true);
            $node->addChild(new TextNode($content));

            $this->lastPos = $endOfClose;
            return $node;
        }

        // No match found — emit the entire opening backtick string as literal text
        $this->lastPos = $pos; // past all opening backticks
        return new TextNode(str_repeat('`', $tickCount));
    }

    // ---------------------------------------------------------------
    // Backslash escape
    // ---------------------------------------------------------------

    private function parseBackslash(string $text, int $pos, int $len): ?Node
    {
        if ($pos + 1 >= $len) {
            $this->lastPos = $pos + 1;
            return new TextNode('\\');
        }

        $next = $text[$pos + 1];

        // Hard line break: backslash before newline
        if ($next === "\n") {
            $this->lastPos = $pos + 2;
            return new ElementNode('break');
        }

        // Escapable characters per CommonMark spec (ASCII punctuation only)
        if (strpos('!"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~', $next) !== false) {
            $this->lastPos = $pos + 2;
            return new TextNode($next);
        }

        $this->lastPos = $pos + 1;
        return new TextNode('\\');
    }

    // ---------------------------------------------------------------
    // Emphasis delimiter (* and _)
    // ---------------------------------------------------------------

    /**
     * @return true|Node|null true = handled via delimiter stack, Node = literal text
     */
    private function parseEmphasisDelimiter(string $text, int $pos, int $len, string $char, array &$inlines, DelimiterStack $delimStack, string &$textBuf): Node|bool|null
    {
        // Count delimiter run
        $start = $pos;
        while ($pos < $len && $text[$pos] === $char) {
            $pos++;
        }
        $count = $pos - $start;

        // Determine can_open and can_close per spec
        $before = $this->unicodeCharBefore($text, $start);
        $after = $this->unicodeCharAfter($text, $pos, $len);

        $leftFlanking = $this->isLeftFlankingDelimiter($before, $after);
        $rightFlanking = $this->isRightFlankingDelimiter($before, $after);

        if ($char === '_') {
            $canOpen = $leftFlanking && (!$rightFlanking || $this->isPunctuation($before));
            $canClose = $rightFlanking && (!$leftFlanking || $this->isPunctuation($after));
        } else {
            $canOpen = $leftFlanking;
            $canClose = $rightFlanking;
        }

        if (!$canOpen && !$canClose) {
            // Treat entire delimiter run as literal text
            $this->lastPos = $pos;
            return new TextNode(str_repeat($char, $count));
        }

        // Flush text buffer first
        if ($textBuf !== '') {
            $inlines[] = new TextNode($textBuf);
            $textBuf = '';
        }

        $delimText = str_repeat($char, $count);
        $inlines[] = new TextNode($delimText);

        $delimiter = new Delimiter(
            $char,
            $count,
            $count,
            $canOpen,
            $canClose,
            count($inlines) - 1,
        );
        $delimStack->push($delimiter);

        $this->lastPos = $pos;
        return true;
    }

    // ---------------------------------------------------------------
    // Link [ and ]
    // ---------------------------------------------------------------

    /**
     * @return true|null
     */
    private function parseLinkOpen(string $text, int $pos, int $len, array &$inlines, DelimiterStack $delimStack, string &$textBuf): ?bool
    {
        // Flush text buffer
        if ($textBuf !== '') {
            $inlines[] = new TextNode($textBuf);
            $textBuf = '';
        }

        $inlines[] = new TextNode('[');

        $delimiter = new Delimiter(
            '[',
            1,
            1,
            true,
            false,
            count($inlines) - 1,
            $pos + 1, // sourcePos: position after [
        );
        $delimStack->push($delimiter);

        $this->lastPos = $pos + 1;
        return true;
    }

    /**
     * @return true|null
     */
    private function parseImageOpen(string $text, int $pos, int $len, array &$inlines, DelimiterStack $delimStack, string &$textBuf): ?bool
    {
        if ($pos + 1 < $len && $text[$pos + 1] === '[') {
            if ($textBuf !== '') {
                $inlines[] = new TextNode($textBuf);
                $textBuf = '';
            }

            $inlines[] = new TextNode('![');

            $delimiter = new Delimiter(
                '!',
                1,
                1,
                true,
                false,
                count($inlines) - 1,
                $pos + 2, // sourcePos: position after ![
            );
            $delimStack->push($delimiter);

            $this->lastPos = $pos + 2;
            return true;
        }

        return null;
    }

    /**
     * @return true|Node|null
     */
    private function parseLinkClose(string $text, int $pos, int $len, array &$inlines, DelimiterStack $delimStack, string &$textBuf): Node|bool|null
    {
        // Flush text buffer
        if ($textBuf !== '') {
            $inlines[] = new TextNode($textBuf);
            $textBuf = '';
        }

        // Look back for matching [ or ![ delimiter (find closest one)
        $opener = null;
        $openerType = '';
        foreach (array_reverse($delimStack->getAll(), true) as $d) {
            if ($d->char === '[' || $d->char === '!') {
                $opener = $d;
                $openerType = $d->char === '!' ? 'image' : 'url';
                break;
            }
        }

        if ($opener === null) {
            $this->lastPos = $pos + 1;
            return null;
        }

        // If the opener is deactivated, remove it and return ] as literal text
        if (!$opener->canOpen) {
            $delimStack->remove($opener);
            $this->lastPos = $pos + 1;
            return null;
        }

        $afterPos = $pos + 1;

        // Try inline link: ](url "title")
        $linkResult = $this->tryInlineLink($text, $afterPos, $len);
        if ($linkResult === null) {
            // Try reference link: ][ref] or ][] or ]
            $linkResult = $this->tryReferenceLink($text, $afterPos, $len, $opener, $inlines);
        }

        if ($linkResult === null) {
            // No valid link — remove opener from stack, treat ] as text
            $delimStack->remove($opener);
            $this->lastPos = $pos + 1;
            return null;
        }

        [$destination, $title, $newPos] = $linkResult;

        // Build the link/image node
        $tagName = $openerType;
        $attrs = $tagName === 'image'
            ? ['src' => $destination, 'title' => $title]
            : ['href' => $destination, 'title' => $title];

        $node = new ElementNode($tagName, $attrs);

        // Process emphasis for content between opener and closer
        $delimStack->processEmphasis($inlines, $opener);

        // Collect inlines from after opener to current position
        $openerIdx = $opener->inlineIndex;
        $innerNodes = array_splice($inlines, $openerIdx + 1);

        // Remove the opener text node
        array_pop($inlines); // Remove the [ or ![ text

        // For images, collect alt text
        if ($tagName === 'image') {
            $altText = '';
            foreach ($innerNodes as $inner) {
                $altText .= $this->extractText($inner);
            }
            $node->setAttribute('alt', $altText);
            // Images can still have child nodes for rendering
            foreach ($innerNodes as $inner) {
                $node->addChild($inner);
            }
        } else {
            foreach ($innerNodes as $inner) {
                $node->addChild($inner);
            }
        }

        $inlines[] = $node;

        // Remove opener from delimiter stack
        $delimStack->remove($opener);

        // For links (not images), deactivate any [ delimiters before this
        if ($tagName === 'url') {
            foreach ($delimStack->getAll() as $d) {
                if ($d->char === '[') {
                    $d->canOpen = false;
                }
            }
        }

        $this->lastPos = $newPos;
        return true;
    }

    /**
     * @return array{string, string, int}|null [destination, title, newPos]
     */
    private function tryInlineLink(string $text, int $pos, int $len): ?array
    {
        if ($pos >= $len || $text[$pos] !== '(') {
            return null;
        }

        $pos++; // skip (
        // Skip whitespace
        while ($pos < $len && ($text[$pos] === ' ' || $text[$pos] === "\n")) {
            $pos++;
        }

        if ($pos >= $len) {
            return null;
        }

        // Parse destination
        $destination = '';
        if ($text[$pos] === '<') {
            // Angle-bracketed destination
            $pos++;
            $start = $pos;
            while ($pos < $len && $text[$pos] !== '>' && $text[$pos] !== "\n") {
                if ($text[$pos] === '\\' && $pos + 1 < $len) {
                    $pos++;
                }
                $pos++;
            }
            if ($pos >= $len || $text[$pos] !== '>') {
                return null;
            }
            $destination = substr($text, $start, $pos - $start);
            $destination = $this->unescapeString($destination);
            $pos++;
        } elseif ($text[$pos] !== ')') {
            // Non-angle-bracketed destination
            $parenDepth = 0;
            $start = $pos;
            while ($pos < $len) {
                $c = $text[$pos];
                if ($c === '\\' && $pos + 1 < $len) {
                    $pos += 2;
                    continue;
                }
                if ($c === '(') {
                    $parenDepth++;
                } elseif ($c === ')') {
                    if ($parenDepth === 0) {
                        break;
                    }
                    $parenDepth--;
                } elseif ($c === ' ' || $c === "\n" || ord($c) < 0x20) {
                    break;
                }
                $pos++;
            }
            $destination = substr($text, $start, $pos - $start);
            $destination = $this->unescapeString($destination);
        }

        // Skip whitespace
        while ($pos < $len && ($text[$pos] === ' ' || $text[$pos] === "\n")) {
            $pos++;
        }

        // Parse optional title
        $title = '';
        if ($pos < $len && ($text[$pos] === '"' || $text[$pos] === '\'' || $text[$pos] === '(')) {
            $closeChar = $text[$pos] === '(' ? ')' : $text[$pos];
            $pos++;
            $start = $pos;
            while ($pos < $len && $text[$pos] !== $closeChar) {
                if ($text[$pos] === '\\' && $pos + 1 < $len) {
                    $pos++;
                }
                $pos++;
            }
            if ($pos >= $len) {
                return null;
            }
            $title = substr($text, $start, $pos - $start);
            $title = $this->unescapeString($title);
            $pos++;
        }

        // Skip whitespace
        while ($pos < $len && ($text[$pos] === ' ' || $text[$pos] === "\n")) {
            $pos++;
        }

        // Must end with )
        if ($pos >= $len || $text[$pos] !== ')') {
            return null;
        }

        return [$this->decodeEntities($destination), $this->decodeEntities($title), $pos + 1];
    }

    /**
     * @return array{string, string, int}|null [destination, title, newPos]
     */
    private function tryReferenceLink(string $text, int $pos, int $len, Delimiter $opener, array &$inlines): ?array
    {
        // Full reference: ][label]
        if ($pos < $len && $text[$pos] === '[') {
            // Find closing ] handling backslash escapes
            $labelStart = $pos + 1;
            $j = $labelStart;
            while ($j < $len) {
                if ($text[$j] === '\\' && $j + 1 < $len) {
                    $j += 2;
                    continue;
                }
                if ($text[$j] === ']') {
                    break;
                }
                if ($text[$j] === '[') {
                    // Unescaped [ in label — invalid
                    break;
                }
                $j++;
            }
            if ($j < $len && $text[$j] === ']') {
                $label = substr($text, $labelStart, $j - $labelStart);
                if ($label !== '') {
                    $ref = $this->refMap->get($label);
                    if ($ref !== null) {
                        return [$ref['destination'], $ref['title'], $j + 1];
                    }
                }
            }
        }

        // Collapsed reference: ][]
        if ($pos + 1 < $len && $text[$pos] === '[' && $text[$pos + 1] === ']') {
            // Use raw source text for label matching (not parsed inline content)
            $rawLabel = substr($text, $opener->sourcePos, $pos - 1 - $opener->sourcePos);
            $ref = $this->refMap->get($rawLabel);
            if ($ref !== null) {
                return [$ref['destination'], $ref['title'], $pos + 2];
            }
        }

        // Shortcut reference: just ]
        // Per spec: shortcut reference cannot be followed by a link label [...]
        if ($pos < $len && $text[$pos] === '[') {
            return null;
        }
        // Use raw source text for label matching (not parsed inline content)
        $rawLabel = substr($text, $opener->sourcePos, $pos - 1 - $opener->sourcePos);
        $ref = $this->refMap->get($rawLabel);
        if ($ref !== null) {
            return [$ref['destination'], $ref['title'], $pos];
        }

        return null;
    }

    // ---------------------------------------------------------------
    // Autolinks
    // ---------------------------------------------------------------

    private function parseAutolink(string $text, int $pos, int $len): ?Node
    {
        // Try URI autolink: <scheme:path>
        if (preg_match('/^<([a-zA-Z][a-zA-Z0-9+.-]{1,31}:[^\s<>]*)>/', substr($text, $pos), $matches)) {
            $uri = $matches[1];
            $node = new ElementNode('url', ['href' => $uri]);
            $node->addChild(new TextNode($uri));
            $this->lastPos = $pos + strlen($matches[0]);
            return $node;
        }

        // Try email autolink: <email@example.com>
        if (preg_match('/^<([a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*)>/', substr($text, $pos), $matches)) {
            $email = $matches[1];
            $node = new ElementNode('url', ['href' => 'mailto:' . $email]);
            $node->addChild(new TextNode($email));
            $this->lastPos = $pos + strlen($matches[0]);
            return $node;
        }

        // Try raw HTML inline
        return $this->parseHtmlInline($text, $pos, $len);
    }

    // ---------------------------------------------------------------
    // Raw HTML inline
    // ---------------------------------------------------------------

    /**
     * Check if an HTML tag is in the GFM disallowed raw HTML list
     */
    private function isDisallowedHtmlTag(string $html): bool
    {
        if (!$this->registry->has('strike')) {
            return false; // Not in GFM mode
        }
        // Extract tag name (case-insensitive)
        if (preg_match('/^<\/?([a-zA-Z][a-zA-Z0-9-]*)/', $html, $m)) {
            $tagName = strtolower($m[1]);
            return in_array($tagName, [
                'title', 'textarea', 'style', 'xmp', 'iframe',
                'noembed', 'noframes', 'script', 'plaintext',
            ], true);
        }
        return false;
    }

    private function parseHtmlInline(string $text, int $pos, int $len): ?Node
    {
        $sub = substr($text, $pos);

        // Open tag
        if (preg_match('/^<[a-zA-Z][a-zA-Z0-9-]*(?:\s+[a-zA-Z_:][a-zA-Z0-9_.:-]*(?:\s*=\s*(?:[^\s"\'=<>`]+|\'[^\']*\'|"[^"]*"))?)*\s*\/?>/', $sub, $m)) {
            $rawHtml = $m[0];
            // GFM disallowed raw HTML: filter certain tags
            if ($this->isDisallowedHtmlTag($rawHtml)) {
                $rawHtml = '&lt;' . substr($rawHtml, 1);
            }
            $node = new ElementNode('htmlinline');
            $node->setVerbatim(true);
            $node->addChild(new TextNode($rawHtml));
            $this->lastPos = $pos + strlen($m[0]);
            return $node;
        }

        // Closing tag
        if (preg_match('/^<\/[a-zA-Z][a-zA-Z0-9-]*\s*>/', $sub, $m)) {
            $rawHtml = $m[0];
            if ($this->isDisallowedHtmlTag($rawHtml)) {
                $rawHtml = '&lt;' . substr($rawHtml, 1);
            }
            $node = new ElementNode('htmlinline');
            $node->setVerbatim(true);
            $node->addChild(new TextNode($rawHtml));
            $this->lastPos = $pos + strlen($m[0]);
            return $node;
        }

        // Comment — per spec:
        //   An HTML comment is <!--->, <!-->, or <!-- + text not containing --> + -->
        if (str_starts_with($sub, '<!--')) {
            $commentMatch = null;
            // Special cases: <!--> and <!--->
            if (str_starts_with($sub, '<!-->')) {
                $commentMatch = '<!---->';
                // Actually per spec, <!--> is the complete comment
                $commentMatch = '<!-->';
            } elseif (str_starts_with($sub, '<!--->')) {
                $commentMatch = '<!--->';
            } elseif (preg_match('/^<!--(?!>)(?!-?>)[\s\S]*?-->/s', $sub, $m)) {
                // General case: <!-- text --> where text doesn't start with > or ->
                $commentMatch = $m[0];
            }
            if ($commentMatch !== null) {
                $node = new ElementNode('htmlinline');
                $node->setVerbatim(true);
                $node->addChild(new TextNode($commentMatch));
                $this->lastPos = $pos + strlen($commentMatch);
                return $node;
            }
        }

        // Processing instruction
        if (preg_match('/^<\?.*?\?>/', $sub, $m)) {
            $node = new ElementNode('htmlinline');
            $node->setVerbatim(true);
            $node->addChild(new TextNode($m[0]));
            $this->lastPos = $pos + strlen($m[0]);
            return $node;
        }

        // CDATA
        if (preg_match('/^<!\[CDATA\[.*?\]\]>/s', $sub, $m)) {
            $node = new ElementNode('htmlinline');
            $node->setVerbatim(true);
            $node->addChild(new TextNode($m[0]));
            $this->lastPos = $pos + strlen($m[0]);
            return $node;
        }

        // Declaration
        if (preg_match('/^<![A-Z]+\s+[^>]*>/', $sub, $m)) {
            $node = new ElementNode('htmlinline');
            $node->setVerbatim(true);
            $node->addChild(new TextNode($m[0]));
            $this->lastPos = $pos + strlen($m[0]);
            return $node;
        }

        return null;
    }

    // ---------------------------------------------------------------
    // Entity
    // ---------------------------------------------------------------

    private function parseEntity(string $text, int $pos, int $len): ?Node
    {
        $sub = substr($text, $pos);

        // Named entity: &amp; etc.
        if (preg_match('/^&([a-zA-Z][a-zA-Z0-9]{1,31});/', $sub, $m)) {
            $decoded = html_entity_decode($m[0], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($decoded !== $m[0]) {
                $this->lastPos = $pos + strlen($m[0]);
                return new TextNode($decoded);
            }
        }

        // Numeric entity: &#123; or &#x1a;
        if (preg_match('/^&#([0-9]{1,7});/', $sub, $m)) {
            $code = (int) $m[1];
            $this->lastPos = $pos + strlen($m[0]);
            if ($code === 0 || $code > 0x10FFFF) {
                return new TextNode("\u{FFFD}");
            }
            return new TextNode(mb_chr($code, 'UTF-8'));
        }
        if (preg_match('/^&#[xX]([0-9a-fA-F]{1,6});/', $sub, $m)) {
            $code = hexdec($m[1]);
            $this->lastPos = $pos + strlen($m[0]);
            if ($code === 0 || $code > 0x10FFFF) {
                return new TextNode("\u{FFFD}");
            }
            return new TextNode(mb_chr((int) $code, 'UTF-8'));
        }

        return null;
    }

    // ---------------------------------------------------------------
    // GFM Strikethrough
    // ---------------------------------------------------------------

    /**
     * @return true|null
     */
    private function parseStrikethrough(string $text, int $pos, int $len, array &$inlines, DelimiterStack $delimStack, string &$textBuf): ?bool
    {
        if (!$this->registry->has('strike')) {
            return null;
        }

        // Count tildes
        $start = $pos;
        while ($pos < $len && $text[$pos] === '~') {
            $pos++;
        }
        $count = $pos - $start;

        if ($count !== 2) {
            $this->lastPos = $start + 1;
            return null; // Only ~~ is valid
        }

        $before = $start > 0 ? $text[$start - 1] : "\n";
        $after = $pos < $len ? $text[$pos] : "\n";

        $canOpen = !ctype_space($after);
        $canClose = !ctype_space($before);

        if (!$canOpen && !$canClose) {
            return null;
        }

        if ($textBuf !== '') {
            $inlines[] = new TextNode($textBuf);
            $textBuf = '';
        }

        $inlines[] = new TextNode('~~');

        $delimiter = new Delimiter(
            '~',
            2,
            2,
            $canOpen,
            $canClose,
            count($inlines) - 1,
        );
        $delimStack->push($delimiter);

        $this->lastPos = $pos;
        return true;
    }

    // ---------------------------------------------------------------
    // Newline (hard break / soft break)
    // ---------------------------------------------------------------

    /**
     * @return Node|null
     */
    private function parseNewline(string $text, int $pos, int $len, string &$textBuf): ?Node
    {
        // Hard break: two or more spaces before newline, or backslash (handled in parseBackslash)
        if (strlen($textBuf) >= 2 && substr($textBuf, -2) === '  ') {
            // Remove trailing spaces from text buffer
            $textBuf = rtrim($textBuf, ' ');
            $this->lastPos = $pos + 1;
            return new ElementNode('break');
        }

        // Soft break
        $textBuf = rtrim($textBuf, ' ');
        $this->lastPos = $pos + 1;
        return new ElementNode('softbreak');
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    private function isLeftFlankingDelimiter(string $before, string $after): bool
    {
        if ($this->isUnicodeWhitespace($after)) {
            return false;
        }
        if (!$this->isPunctuation($after)) {
            return true;
        }
        // After is punctuation: must be preceded by space or punctuation
        return $this->isUnicodeWhitespace($before) || $this->isPunctuation($before);
    }

    private function isRightFlankingDelimiter(string $before, string $after): bool
    {
        if ($this->isUnicodeWhitespace($before)) {
            return false;
        }
        if (!$this->isPunctuation($before)) {
            return true;
        }
        // Before is punctuation: must be followed by space or punctuation
        return $this->isUnicodeWhitespace($after) || $this->isPunctuation($after);
    }

    private function isUnicodeWhitespace(string $char): bool
    {
        if ($char === '') {
            return false;
        }
        // Unicode Zs category + ASCII whitespace + line endings
        return (bool) preg_match('/^[\s\x{00a0}\x{1680}\x{2000}-\x{200A}\x{202F}\x{205F}\x{3000}]$/u', $char);
    }

    /**
     * Get the Unicode character before a byte position
     */
    private function unicodeCharBefore(string $text, int $pos): string
    {
        if ($pos <= 0) {
            return "\n";
        }
        // Walk back up to 4 bytes to find the start of the UTF-8 character
        $sub = substr($text, max(0, $pos - 4), min($pos, 4));
        if ($sub === '' || $sub === false) {
            return "\n";
        }
        // Get the last character (may be multi-byte)
        $chars = mb_str_split($sub, 1, 'UTF-8');
        return end($chars) ?: "\n";
    }

    /**
     * Get the Unicode character at a byte position
     */
    private function unicodeCharAfter(string $text, int $pos, int $len): string
    {
        if ($pos >= $len) {
            return "\n";
        }
        // Get up to 4 bytes and extract the first character
        $sub = substr($text, $pos, 4);
        if ($sub === '' || $sub === false) {
            return "\n";
        }
        return mb_substr($sub, 0, 1, 'UTF-8');
    }

    private function isPunctuation(string $char): bool
    {
        if ($char === '') {
            return false;
        }
        // CommonMark spec: ASCII punctuation + Unicode categories Pc, Pd, Pe, Pf, Pi, Po, Ps, Sc, Sk, Sm, So
        return (bool) preg_match('/^[\x{21}-\x{2F}\x{3A}-\x{40}\x{5B}-\x{60}\x{7B}-\x{7E}\p{P}\p{S}]$/u', $char);
    }

    private function extractText(Node $node): string
    {
        if ($node instanceof TextNode) {
            return $node->getText();
        }
        if ($node instanceof ElementNode) {
            $text = '';
            foreach ($node->getChildren() as $child) {
                $text .= $this->extractText($child);
            }
            return $text;
        }
        return '';
    }

    private function extractTextFromInlines(array $inlines, int $fromIndex): string
    {
        $text = '';
        for ($i = $fromIndex; $i < count($inlines); $i++) {
            $text .= $this->extractText($inlines[$i]);
        }
        return $text;
    }

    private function unescapeString(string $str): string
    {
        // Unescape backslash escapes
        return preg_replace_callback('/\\\\([!"#$%&\'()*+,\-.\\/:;<=>?@\[\\\\\\]^_`{|}~])/', function ($m) {
            return $m[1];
        }, $str) ?? $str;
    }

    private function decodeEntities(string $str): string
    {
        return html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    // ---------------------------------------------------------------
    // GFM Extended Autolinks
    // ---------------------------------------------------------------

    /**
     * Try to parse www./http/https extended autolinks
     */
    private function parseExtendedAutolink(string $text, int $pos, int $len): ?Node
    {
        if (!$this->registry->has('strike')) {
            return null; // Not in GFM mode
        }

        $sub = substr($text, $pos);

        // Extended autolinks must occur after whitespace, start-of-line,
        // or certain delimiter characters
        if ($pos > 0) {
            $before = $text[$pos - 1];
            if (!ctype_space($before) && $before !== '*' && $before !== '_'
                && $before !== '~' && $before !== '(' && $before !== '"' && $before !== "'") {
                return null;
            }
        }

        // Try http:// or https:// or ftp:// autolink
        if (preg_match('/^(?:https?|ftp):\/\/[^\s<]*/i', $sub, $m)) {
            $url = $this->trimExtendedAutolink($m[0]);
            if (strlen($url) > 0) {
                $node = new ElementNode('url', ['href' => $url]);
                $node->addChild(new TextNode($url));
                $this->lastPos = $pos + strlen($url);
                return $node;
            }
        }

        // Try www. autolink
        if (preg_match('/^www\.[^\s<]*/i', $sub, $m)) {
            $url = $this->trimExtendedAutolink($m[0]);
            if (strlen($url) > 0) {
                $node = new ElementNode('url', ['href' => 'http://' . $url]);
                $node->addChild(new TextNode($url));
                $this->lastPos = $pos + strlen($url);
                return $node;
            }
        }

        return null;
    }

    /**
     * Try to parse email extended autolinks (any character position)
     */
    private function parseExtendedAutolinkEmail(string $text, int $pos, int $len): ?Node
    {
        if (!$this->registry->has('strike')) {
            return null; // Not in GFM mode
        }

        $char = $text[$pos];

        // Email local part starts with alphanumeric
        if (!ctype_alnum($char)) {
            return null;
        }

        // Quick check: must have @ ahead
        $sub = substr($text, $pos);
        $atPos = strpos($sub, '@');
        if ($atPos === false || $atPos < 1) {
            return null;
        }

        // GFM email autolink: [a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[domain]
        // But the local part must NOT contain + for the extended autolink to be recognized
        // (per GFM spec example 630: hello@mail+xyz.example isn't valid)
        if (preg_match('/^[a-zA-Z0-9.+_-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)+/', $sub, $m)) {
            $email = $m[0];
            $afterEmail = $pos + strlen($email) < $len ? $text[$pos + strlen($email)] : '';
            // Per GFM spec: if character after match is - or _, not an autolink
            if ($afterEmail === '-' || $afterEmail === '_') {
                return null;
            }
            // Per GFM spec: last character must not be - or _
            if ($email[-1] === '-' || $email[-1] === '_') {
                return null;
            }
            // Trim trailing dots
            $email = rtrim($email, '.');
            // Verify still has @ and domain dot after trimming
            if (!str_contains($email, '@') || !preg_match('/@.+\./', $email)) {
                return null;
            }

            $node = new ElementNode('url', ['href' => 'mailto:' . $email]);
            $node->addChild(new TextNode($email));
            $this->lastPos = $pos + strlen($email);
            return $node;
        }

        return null;
    }

    /**
     * Trim trailing punctuation from an extended autolink per GFM spec
     */
    private function trimExtendedAutolink(string $url): string
    {
        // Iteratively trim trailing characters per GFM rules
        $changed = true;
        while ($changed && strlen($url) > 0) {
            $changed = false;

            // Trim trailing entity-like sequences: &xxx;
            if (preg_match('/&[a-zA-Z0-9]+;$/', $url)) {
                $url = preg_replace('/&[a-zA-Z0-9]+;$/', '', $url) ?? $url;
                $changed = true;
                continue;
            }

            // Trim trailing punctuation: ? ! . , : * _ ~ ' " ;
            $last = $url[-1] ?? '';
            if (strpos("?!.,:*_~'\"", $last) !== false) {
                $url = substr($url, 0, -1);
                $changed = true;
                continue;
            }

            // Balance trailing parentheses
            if ($last === ')') {
                $open = substr_count($url, '(');
                $close = substr_count($url, ')');
                if ($close > $open) {
                    $url = substr($url, 0, -1);
                    $changed = true;
                    continue;
                }
            }

            // Trim trailing ;
            if ($last === ';') {
                $url = substr($url, 0, -1);
                $changed = true;
                continue;
            }
        }

        // Truncate at < (per spec: < not allowed in extended autolinks)
        if (str_contains($url, '<')) {
            $url = substr($url, 0, strpos($url, '<'));
        }

        return $url;
    }
}
