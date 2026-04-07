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
 * Cowiki markup renderer for typed AST
 *
 * Renders the typed document tree back to Cowiki wiki markup.
 * Designed for idempotency: parse → render → parse → render stabilizes.
 *
 * Cowiki uses single-character delimiters for inline formatting
 * (*bold*, /italic/, _underline_, =monospace=) and HTML-style tags
 * for structural elements (<code>, <sup>, <sub>, <noop>, <table>, <toc>).
 * Links use double-paren syntax: ((url)(text)).
 *
 * @author   Daniel T. Gorski
 * @author   Justin Patrin <papercrane@reversefold.com>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class Cowiki implements Renderer, NodeVisitor
{
    private const BLOCK_ELEMENTS = [
        'heading', 'horiz', 'code', 'raw', 'blockquote',
        'paragraph', 'table', 'list', 'toc',
    ];

    private int $listDepth = 0;

    /** @var array<string> Stack of 'bullet'|'number' per nesting level */
    private array $listTypeStack = [];

    public function render(DocumentNode $document): string
    {
        $this->listDepth = 0;
        $this->listTypeStack = [];

        return $this->visitDocument($document);
    }

    public function getFormat(): string
    {
        return 'cowiki';
    }

    public function visitDocument(DocumentNode $node): string
    {
        return $this->renderNodeList($node->getChildren());
    }

    public function visitElement(ElementNode $node): string
    {
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

    /**
     * Render a list of child nodes with block-level separation
     *
     * @param array<Node> $children Child nodes to render
     *
     * @return string Rendered output
     */
    protected function renderNodeList(array $children): string
    {
        $output = '';
        $count = count($children);

        for ($i = 0; $i < $count; $i++) {
            $child = $children[$i];

            // Skip whitespace-only text nodes adjacent to block elements
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

            // Block elements always start on a new line
            if ($isBlock && $output !== '' && !str_ends_with($output, "\n")) {
                $output .= "\n";
            }

            $output .= $rendered;

            // Block elements always end with a newline
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

    protected function renderChildren(ElementNode $node): string
    {
        return $this->renderNodeList($node->getChildren());
    }

    // ---------------------------------------------------------------
    // Inline formatting (single-character symmetric delimiters)
    // ---------------------------------------------------------------

    protected function renderBold(ElementNode $node): string
    {
        return '*' . $this->renderChildren($node) . '*';
    }

    protected function renderItalic(ElementNode $node): string
    {
        return '/' . $this->renderChildren($node) . '/';
    }

    protected function renderUnderline(ElementNode $node): string
    {
        return '_' . $this->renderChildren($node) . '_';
    }

    protected function renderTt(ElementNode $node): string
    {
        return '=' . $this->renderChildren($node) . '=';
    }

    // ---------------------------------------------------------------
    // Inline HTML tags (superscript, subscript)
    // ---------------------------------------------------------------

    protected function renderSuperscript(ElementNode $node): string
    {
        return '<sup>' . $this->renderChildren($node) . '</sup>';
    }

    protected function renderSubscript(ElementNode $node): string
    {
        return '<sub>' . $this->renderChildren($node) . '</sub>';
    }

    // ---------------------------------------------------------------
    // Links
    // ---------------------------------------------------------------

    protected function renderUrl(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $href = $attrs['href'] ?? '';
        $text = $this->renderChildren($node);

        // If no href attribute, get URL from text content
        if ($href === '') {
            $children = $node->getChildren();
            if (count($children) === 1 && $children[0] instanceof TextNode) {
                $href = $children[0]->getText();
            }
        }

        // Described link: ((href)(text))
        if ($text !== '' && $text !== $href) {
            return '((' . $href . ')(' . $text . '))';
        }

        // Bare URL — just emit the URL directly
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

        // With display text: ((page)(text))
        if ($text !== '' && $text !== $target && $text !== $page) {
            return '((' . $target . ')(' . $text . '))';
        }

        // Bare wikilink: ((page))
        return '((' . $target . '))';
    }

    // ---------------------------------------------------------------
    // Block elements
    // ---------------------------------------------------------------

    protected function renderHeading(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $level = max(1, min(6, (int) ($attrs['level'] ?? 1)));

        return str_repeat('+', $level) . ' ' . $this->renderChildren($node);
    }

    protected function renderHoriz(ElementNode $node): string
    {
        return '---';
    }

    protected function renderCode(ElementNode $node): string
    {
        $content = $this->renderChildren($node);

        return "<code>\n" . $content . "\n</code>";
    }

    protected function renderRaw(ElementNode $node): string
    {
        return '<noop>' . $this->renderChildren($node) . '</noop>';
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

    protected function renderToc(ElementNode $node): string
    {
        return '<toc>';
    }

    protected function renderParagraph(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }

    // ---------------------------------------------------------------
    // Lists
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
        $indent = str_repeat(' ', $this->listDepth - 1);
        $marker = (!empty($this->listTypeStack) && end($this->listTypeStack) === 'number')
            ? '#'
            : '*';

        // Render children directly — sublists follow without extra separation
        $content = '';
        foreach ($node->getChildren() as $child) {
            $rendered = $child->accept($this);
            if ($child instanceof ElementNode && $child->getName() === 'list') {
                // Sublist starts on the next line, no double newlines
                $content = rtrim($content) . "\n" . $rendered;
            } else {
                $content .= $rendered;
            }
        }

        return $indent . $marker . ' ' . $content;
    }

    /**
     * Render list children, joining items with newlines
     */
    private function renderListChildren(ElementNode $node): string
    {
        $items = [];
        foreach ($node->getChildren() as $child) {
            $items[] = $child->accept($this);
        }
        return implode("\n", $items);
    }

    // ---------------------------------------------------------------
    // Tables (HTML format — Cowiki uses HTML table tags)
    // ---------------------------------------------------------------

    protected function renderTable(ElementNode $node): string
    {
        $rows = [];
        foreach ($node->getChildren() as $child) {
            if ($child instanceof ElementNode && $child->getName() === 'row') {
                $rows[] = $this->renderRow($child);
            }
        }
        return "<table>\n" . implode("\n", $rows) . "\n</table>";
    }

    protected function renderRow(ElementNode $node): string
    {
        $cells = '';
        foreach ($node->getChildren() as $child) {
            if ($child instanceof ElementNode && $child->getName() === 'cell') {
                $cells .= $this->renderCell($child);
            }
        }
        return '<tr>' . $cells . '</tr>';
    }

    private function renderCell(ElementNode $child): string
    {
        $attrs = $child->getAttributes();
        $content = $this->renderChildren($child);
        $isHeader = ($attrs['type'] ?? 'data') === 'header';
        $tag = $isHeader ? 'th' : 'td';

        return '<' . $tag . '>' . $content . '</' . $tag . '>';
    }
}
