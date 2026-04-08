<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Renderer;

use Horde\Text\Wiki\Node\DocumentNode;
use Horde\Text\Wiki\Node\ElementNode;
use Horde\Text\Wiki\Node\Node;
use Horde\Text\Wiki\Node\TextNode;
use Horde\Text\Wiki\NodeVisitor;
use Horde\Text\Wiki\Renderer;

/**
 * DokuWiki markup renderer for typed AST
 *
 * Renders the typed document tree back to DokuWiki markup.
 * Designed for idempotency: parse -> render -> parse -> render stabilizes.
 *
 * DokuWiki core syntax:
 * - **bold**, //italic//, __underline__, ''monospace''
 * - <sup>superscript</sup>, <sub>subscript</sub>, <del>strikethrough</del>
 * - Headings: ====== H1 ====== to == H5 == (inverted: more = = higher)
 * - Links: [[url|text]] or bare URLs
 * - Images: {{src|alt}}
 * - Lists: 2-space indent + * (bullet) or - (numbered)
 * - Tables: | data cells, ^ header cells
 * - Code block: <code>...</code>
 * - Nowiki: <nowiki>...</nowiki>
 * - Break: \\, horiz: ----
 * - Blockquote: > prefix
 * - Center: ::text::
 * - Deflist: ; term ; definition
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class Doku implements Renderer, NodeVisitor
{
    /** @var array<string, callable(ElementNode, NodeVisitor): string> */
    private array $elementHandlers = [];

    private const BLOCK_ELEMENTS = [
        'heading', 'horiz', 'code', 'raw', 'blockquote',
        'paragraph', 'table', 'list', 'center',
        'deflist', 'image',
    ];

    private int $listDepth = 0;

    /** @var array<string> Stack of 'bullet'|'number' per nesting level */
    private array $listTypeStack = [];

    public function setElementHandler(string $tagName, callable $handler): void
    {
        $this->elementHandlers[strtolower($tagName)] = $handler;
    }

    public function render(DocumentNode $document): string
    {
        $this->listDepth = 0;
        $this->listTypeStack = [];

        return $this->visitDocument($document);
    }

    public function getFormat(): string
    {
        return 'doku';
    }

    public function visitDocument(DocumentNode $node): string
    {
        return $this->renderNodeList($node->getChildren());
    }

    public function visitElement(ElementNode $node): string
    {
        $key = strtolower($node->getName());

        if (isset($this->elementHandlers[$key])) {
            return ($this->elementHandlers[$key])($node, $this);
        }

        $tagName = $node->getName();
        $method = 'render' . str_replace('_', '', ucwords($tagName, '_'));

        if (method_exists($this, $method)) {
            return $this->$method($node);
        }

        return $this->renderChildren($node);
    }

    public function visitText(TextNode $node): string
    {
        return $node->getText();
    }

    // ---------------------------------------------------------------
    // Node list rendering with block separation
    // ---------------------------------------------------------------

    protected function renderNodeList(array $children): string
    {
        $output = '';
        $count = count($children);

        for ($i = 0; $i < $count; $i++) {
            $child = $children[$i];

            if ($child instanceof TextNode
                && trim($child->getText()) === ''
                && $this->isAdjacentToBlock($children, $i)) {
                continue;
            }

            $isBlock = $this->isBlockElement($child);
            $rendered = $child->accept($this);

            if ($rendered === '') {
                continue;
            }

            if ($isBlock && $output !== '' && !str_ends_with($output, "\n")) {
                $output .= "\n";
            }

            $output .= $rendered;

            if ($isBlock && !str_ends_with($output, "\n")) {
                $output .= "\n";
            }
        }

        return $output;
    }

    private function isAdjacentToBlock(array $children, int $index): bool
    {
        $prev = $this->findNonWhitespaceNeighbor($children, $index, -1);
        $next = $this->findNonWhitespaceNeighbor($children, $index, 1);

        $prevIsBlock = $prev === null || $this->isBlockElement($prev);
        $nextIsBlock = $next === null || $this->isBlockElement($next);

        return $prevIsBlock || $nextIsBlock;
    }

    private function findNonWhitespaceNeighbor(array $children, int $index, int $direction): ?Node
    {
        $i = $index + $direction;
        while ($i >= 0 && $i < count($children)) {
            $node = $children[$i];
            if (!($node instanceof TextNode && trim($node->getText()) === '')) {
                return $node;
            }
            $i += $direction;
        }
        return null;
    }

    private function isBlockElement(Node $node): bool
    {
        return $node instanceof ElementNode
            && in_array($node->getName(), self::BLOCK_ELEMENTS, true);
    }

    // ---------------------------------------------------------------
    // Child rendering helper
    // ---------------------------------------------------------------

    public function renderChildren(ElementNode $node): string
    {
        return $this->renderNodeList($node->getChildren());
    }

    // ---------------------------------------------------------------
    // Inline formatting
    // ---------------------------------------------------------------

    protected function renderBold(ElementNode $node): string
    {
        return '**' . $this->renderChildren($node) . '**';
    }

    protected function renderItalic(ElementNode $node): string
    {
        return '//' . $this->renderChildren($node) . '//';
    }

    protected function renderUnderline(ElementNode $node): string
    {
        return '__' . $this->renderChildren($node) . '__';
    }

    protected function renderTt(ElementNode $node): string
    {
        return "''" . $this->renderChildren($node) . "''";
    }

    protected function renderSuperscript(ElementNode $node): string
    {
        return '<sup>' . $this->renderChildren($node) . '</sup>';
    }

    protected function renderSubscript(ElementNode $node): string
    {
        return '<sub>' . $this->renderChildren($node) . '</sub>';
    }

    protected function renderDel(ElementNode $node): string
    {
        return '<del>' . $this->renderChildren($node) . '</del>';
    }

    protected function renderBreak(ElementNode $node): string
    {
        return '\\\\';
    }

    // ---------------------------------------------------------------
    // Links
    // ---------------------------------------------------------------

    protected function renderUrl(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $href = $attrs['href'] ?? '';
        $text = $this->renderChildren($node);

        if ($href === '') {
            $children = $node->getChildren();
            if (count($children) === 1 && $children[0] instanceof TextNode) {
                $href = $children[0]->getText();
            }
        }

        if ($text !== '' && $text !== $href) {
            return '[[' . $href . '|' . $text . ']]';
        }

        return $href;
    }

    protected function renderWikilink(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $page = $attrs['page'] ?? '';
        $anchor = $attrs['anchor'] ?? '';
        $text = $this->renderChildren($node);

        $target = $page;
        if ($anchor !== '') {
            $target .= '#' . $anchor;
        }

        if ($text !== '' && $text !== $target && $text !== $page) {
            return '[[' . $target . '|' . $text . ']]';
        }

        return '[[' . $target . ']]';
    }

    protected function renderImage(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $src = $attrs['src'] ?? '';
        $alt = $attrs['alt'] ?? '';

        if ($alt !== '') {
            return '{{' . $src . '|' . $alt . '}}';
        }

        return '{{' . $src . '}}';
    }

    // ---------------------------------------------------------------
    // Block elements
    // ---------------------------------------------------------------

    protected function renderHeading(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $level = max(1, min(5, (int) ($attrs['level'] ?? 1)));

        // DokuWiki inverts: level 1 → 6 equals, level 5 → 2 equals
        $eqCount = 7 - $level;
        $markers = str_repeat('=', $eqCount);

        return $markers . ' ' . $this->renderChildren($node) . ' ' . $markers;
    }

    protected function renderHoriz(ElementNode $node): string
    {
        return '----';
    }

    protected function renderCode(ElementNode $node): string
    {
        $content = $this->renderChildren($node);
        $attrs = $node->getAttributes();
        $lang = $attrs['type'] ?? '';

        if ($lang !== '') {
            return "<code " . $lang . ">\n" . $content . "\n</code>";
        }

        return "<code>\n" . $content . "\n</code>";
    }

    protected function renderRaw(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }

    protected function renderBlockquote(ElementNode $node): string
    {
        $content = $this->renderChildren($node);
        $lines = explode("\n", $content);

        $output = [];
        foreach ($lines as $line) {
            $output[] = '> ' . $line;
        }

        return implode("\n", $output);
    }

    protected function renderCenter(ElementNode $node): string
    {
        return '::' . $this->renderChildren($node) . '::';
    }

    protected function renderParagraph(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }

    // ---------------------------------------------------------------
    // Lists — indentation-based: 2 spaces per level
    // ---------------------------------------------------------------

    protected function renderList(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $type = $attrs['type'] ?? 'bullet';

        $this->listDepth++;
        $this->listTypeStack[] = $type;

        $output = $this->renderListChildren($node);

        array_pop($this->listTypeStack);
        $this->listDepth--;

        return $output;
    }

    protected function renderListitem(ElementNode $node): string
    {
        // DokuWiki uses 2-space indentation per level
        $indent = str_repeat('  ', $this->listDepth);

        // Marker: * for bullet, - for numbered
        $type = end($this->listTypeStack);
        $marker = ($type === 'number') ? '-' : '*';

        $content = '';
        foreach ($node->getChildren() as $child) {
            $rendered = $child->accept($this);
            if ($child instanceof ElementNode && $child->getName() === 'list') {
                $content = rtrim($content) . "\n" . $rendered;
            } else {
                $content .= $rendered;
            }
        }

        return $indent . $marker . ' ' . $content;
    }

    private function renderListChildren(ElementNode $node): string
    {
        $items = [];
        foreach ($node->getChildren() as $child) {
            $items[] = $child->accept($this);
        }
        return implode("\n", $items);
    }

    // ---------------------------------------------------------------
    // Tables — DokuWiki: ^ header cells, | data cells
    // ---------------------------------------------------------------

    protected function renderTable(ElementNode $node): string
    {
        $rows = [];
        foreach ($node->getChildren() as $child) {
            if ($child instanceof ElementNode && $child->getName() === 'row') {
                $rows[] = $this->renderRow($child);
            }
        }

        return implode("\n", $rows);
    }

    protected function renderRow(ElementNode $node): string
    {
        $parts = [];
        $firstDelim = '|';

        foreach ($node->getChildren() as $i => $child) {
            if ($child instanceof ElementNode && $child->getName() === 'cell') {
                $attrs = $child->getAttributes();
                $content = $this->renderChildren($child);
                $isHeader = (($attrs['type'] ?? 'data') === 'header');

                $delim = $isHeader ? '^' : '|';

                if ($i === 0) {
                    $firstDelim = $delim;
                }

                $parts[] = ['delim' => $delim, 'content' => $content];
            }
        }

        if (empty($parts)) {
            return '';
        }

        // Build row: first delimiter + content + alternating delimiters + trailing
        $output = $parts[0]['delim'];
        foreach ($parts as $idx => $part) {
            $output .= ' ' . $part['content'] . ' ';
            if ($idx < count($parts) - 1) {
                $output .= $parts[$idx + 1]['delim'];
            } else {
                // Trailing delimiter matches the cell type
                $output .= $part['delim'];
            }
        }

        return $output;
    }

    // ---------------------------------------------------------------
    // Definition lists
    // ---------------------------------------------------------------

    protected function renderDeflist(ElementNode $node): string
    {
        $children = $node->getChildren();
        $lines = [];
        $count = count($children);

        for ($i = 0; $i < $count; $i++) {
            $child = $children[$i];

            if ($child instanceof ElementNode && $child->getName() === 'defterm') {
                $term = $this->renderChildren($child);
                $def = '';

                // Look for the following defdef
                if ($i + 1 < $count
                    && $children[$i + 1] instanceof ElementNode
                    && $children[$i + 1]->getName() === 'defdef') {
                    $def = $this->renderChildren($children[$i + 1]);
                    $i++;
                }

                $lines[] = '; ' . $term . ' ; ' . $def;
            }
        }

        return implode("\n", $lines);
    }
}
