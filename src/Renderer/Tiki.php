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
 * Tiki markup renderer for typed AST
 *
 * Renders the typed document tree back to Tiki wiki markup.
 * Designed for idempotency: parse -> render -> parse -> render stabilizes.
 *
 * Tiki uses distinctive delimiters for inline formatting
 * (__bold__, ''italic'', ===underline===, -+monospace+-) and
 * plugin-style tags for structural elements ({CODE()}, {toc}, {img}).
 * Links use bracket/pipe syntax: [url|text], ((page|text)).
 * Lists use character repetition for nesting: *, **, ***.
 * Tables use double-pipe rows: ||cell1|cell2||.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class Tiki implements Renderer, NodeVisitor
{
    /** @var array<string, callable(ElementNode, NodeVisitor): string> */
    private array $elementHandlers = [];

    private const BLOCK_ELEMENTS = [
        'heading', 'horiz', 'code', 'raw', 'blockquote',
        'paragraph', 'table', 'list', 'toc', 'center',
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
        return 'tiki';
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
        return '__' . $this->renderChildren($node) . '__';
    }

    protected function renderItalic(ElementNode $node): string
    {
        return "''" . $this->renderChildren($node) . "''";
    }

    protected function renderUnderline(ElementNode $node): string
    {
        return '===' . $this->renderChildren($node) . '===';
    }

    protected function renderTt(ElementNode $node): string
    {
        return '-+' . $this->renderChildren($node) . '+-';
    }

    protected function renderSuperscript(ElementNode $node): string
    {
        return '^^' . $this->renderChildren($node) . '^^';
    }

    protected function renderSubscript(ElementNode $node): string
    {
        return ',,' . $this->renderChildren($node) . ',,';
    }

    protected function renderColortext(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $color = $attrs['color'] ?? '';

        return '~~' . $color . ':' . $this->renderChildren($node) . '~~';
    }

    protected function renderBreak(ElementNode $node): string
    {
        return " _\n";
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

        // Described link: [href|text]
        if ($text !== '' && $text !== $href) {
            return '[' . $href . '|' . $text . ']';
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

        // With display text: ((page|text))
        if ($text !== '' && $text !== $target && $text !== $page) {
            return '((' . $target . '|' . $text . '))';
        }

        // Bare wikilink: ((page))
        return '((' . $target . '))';
    }

    protected function renderAnchor(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $name = $attrs['name'] ?? '';

        return '[[# ' . $name . ']]';
    }

    protected function renderImage(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $parts = [];

        foreach (['src', 'alt', 'width', 'height', 'align', 'desc', 'link'] as $key) {
            if (isset($attrs[$key]) && $attrs[$key] !== '') {
                $parts[] = $key . '="' . $attrs[$key] . '"';
            }
        }

        return '{img ' . implode(' ', $parts) . '}';
    }

    // ---------------------------------------------------------------
    // Block elements
    // ---------------------------------------------------------------

    protected function renderHeading(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $level = max(1, min(6, (int) ($attrs['level'] ?? 1)));

        return str_repeat('!', $level) . ' ' . $this->renderChildren($node);
    }

    protected function renderHoriz(ElementNode $node): string
    {
        return '----';
    }

    protected function renderCode(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $content = $this->renderChildren($node);

        $attrString = '';
        if (isset($attrs['language']) && $attrs['language'] !== '') {
            $attrString = 'lang=' . $attrs['language'];
        }

        return '{CODE(' . $attrString . ")}\n" . $content . "\n{CODE}";
    }

    protected function renderRaw(ElementNode $node): string
    {
        return '~np~' . $this->renderChildren($node) . '~/np~';
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
        return '{toc}';
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
    // Lists — character repetition for nesting
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
        // Build marker by repeating the list type character per depth level
        $marker = '';
        foreach ($this->listTypeStack as $type) {
            $marker .= ($type === 'number') ? '#' : '*';
        }

        $content = '';
        foreach ($node->getChildren() as $child) {
            $rendered = $child->accept($this);
            if ($child instanceof ElementNode && $child->getName() === 'list') {
                $content = rtrim($content) . "\n" . $rendered;
            } else {
                $content .= $rendered;
            }
        }

        return $marker . ' ' . $content;
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
    // Tables — Tiki pipe-delimited format: ||cell1|cell2||
    // ---------------------------------------------------------------

    protected function renderTable(ElementNode $node): string
    {
        $rows = [];
        foreach ($node->getChildren() as $child) {
            if ($child instanceof ElementNode && $child->getName() === 'row') {
                $rows[] = $this->renderRow($child);
            }
        }

        return '||' . implode("\n", $rows) . '||';
    }

    protected function renderRow(ElementNode $node): string
    {
        $cells = [];
        foreach ($node->getChildren() as $child) {
            if ($child instanceof ElementNode && $child->getName() === 'cell') {
                $attrs = $child->getAttributes();
                $content = $this->renderChildren($child);
                $prefix = '';

                if (($attrs['type'] ?? 'data') === 'header') {
                    $prefix = '~';
                } elseif (isset($attrs['align'])) {
                    $prefix = match ($attrs['align']) {
                        'right'  => '>',
                        'center' => '=',
                        'left'   => '<',
                        default  => '',
                    };
                }

                $cells[] = $prefix . $content;
            }
        }

        return implode('|', $cells);
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

                $lines[] = ';' . $term . ':' . $def;
            }
        }

        return implode("\n", $lines);
    }
}
