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
 * Yawiki markup renderer for typed AST
 *
 * Renders the typed document tree back to Yawiki wiki markup.
 * Designed for idempotency: parse → render → parse → render stabilizes.
 *
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   Justin Patrin <papercrane@reversefold.com>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class Yawiki implements Renderer, NodeVisitor
{
    /** @var array<string, callable(ElementNode, NodeVisitor): string> */
    private array $elementHandlers = [];

    private const BLOCK_ELEMENTS = [
        'heading', 'horiz', 'code', 'blockquote', 'center',
        'paragraph', 'table', 'deflist', 'list',
    ];

    private int $listDepth = 0;

    /** @var array<string> Stack of 'bullet'|'number' per nesting level */
    private array $listTypeStack = [];

    /**
     * Set of revise_ins node object IDs already rendered as part of a pair
     *
     * @var array<int, true>
     */
    private array $renderedReviseIns = [];

    public function setElementHandler(string $tagName, callable $handler): void
    {
        $this->elementHandlers[strtolower($tagName)] = $handler;
    }

    public function render(DocumentNode $document): string
    {
        $this->listDepth = 0;
        $this->listTypeStack = [];
        $this->renderedReviseIns = [];

        return $this->visitDocument($document);
    }

    public function getFormat(): string
    {
        return 'yawiki';
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

        // Handle underscore in method names (revise_del, revise_ins)
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
    // Node list rendering with block separation and sibling context
    // ---------------------------------------------------------------

    /**
     * Render a list of child nodes with block-level separation
     *
     * Handles revise_del/revise_ins pairing via sibling lookahead.
     *
     * @param array<Node> $children Child nodes to render
     *
     * @return string Rendered output
     */
    protected function renderNodeList(array $children): string
    {
        $output = '';
        $count = count($children);
        $prevWasBlock = false;

        for ($i = 0; $i < $count; $i++) {
            $child = $children[$i];

            // Skip revise_ins already consumed by a paired revise_del
            if ($child instanceof ElementNode
                && $child->getName() === 'revise_ins'
                && isset($this->renderedReviseIns[spl_object_id($child)])) {
                continue;
            }

            // Skip whitespace-only text nodes adjacent to block elements
            if ($child instanceof TextNode
                && trim($child->getText()) === ''
                && $this->isAdjacentToBlock($children, $i)) {
                continue;
            }

            $isBlock = $this->isBlockElement($child);

            // Render the node
            $rendered = '';
            if ($child instanceof ElementNode && $child->getName() === 'revise_del') {
                $rendered = $this->renderRevisePair($child, $children, $i);
            } elseif ($child instanceof ElementNode && $child->getName() === 'revise_ins') {
                $rendered = '@@+++' . $this->renderChildrenOf($child) . '@@';
            } else {
                $rendered = $child->accept($this);
            }

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

            $prevWasBlock = $isBlock;
        }

        return $output;
    }

    /**
     * Check if a whitespace text node is adjacent to a block element
     */
    private function isAdjacentToBlock(array $children, int $index): bool
    {
        $prev = $this->findNonWhitespaceNeighbor($children, $index, -1);
        $next = $this->findNonWhitespaceNeighbor($children, $index, 1);

        $prevIsBlock = $prev === null || $this->isBlockElement($prev);
        $nextIsBlock = $next === null || $this->isBlockElement($next);

        return $prevIsBlock || $nextIsBlock;
    }

    /**
     * Find the nearest non-whitespace-text sibling in a direction
     */
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

    /**
     * Render a revise_del, optionally paired with a following revise_ins
     */
    private function renderRevisePair(ElementNode $del, array $siblings, int $delIndex): string
    {
        $delText = $this->renderChildrenOf($del);

        $next = $delIndex + 1;
        if ($next < count($siblings)
            && $siblings[$next] instanceof ElementNode
            && $siblings[$next]->getName() === 'revise_ins') {
            $this->renderedReviseIns[spl_object_id($siblings[$next])] = true;
            $insText = $this->renderChildrenOf($siblings[$next]);
            return '@@---' . $delText . '+++' . $insText . '@@';
        }

        return '@@---' . $delText . '@@';
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

    /**
     * Render children directly without revise pairing logic
     *
     * Used by revise pair rendering to avoid recursion.
     */
    private function renderChildrenOf(ElementNode $node): string
    {
        $output = '';
        foreach ($node->getChildren() as $child) {
            $output .= $child->accept($this);
        }
        return $output;
    }

    // ---------------------------------------------------------------
    // Inline elements
    // ---------------------------------------------------------------

    protected function renderBold(ElementNode $node): string
    {
        return "'''" . $this->renderChildren($node) . "'''";
    }

    protected function renderItalic(ElementNode $node): string
    {
        return "''" . $this->renderChildren($node) . "''";
    }

    protected function renderStrong(ElementNode $node): string
    {
        return '**' . $this->renderChildren($node) . '**';
    }

    protected function renderEmphasis(ElementNode $node): string
    {
        return '//' . $this->renderChildren($node) . '//';
    }

    protected function renderUnderline(ElementNode $node): string
    {
        return '__' . $this->renderChildren($node) . '__';
    }

    protected function renderTt(ElementNode $node): string
    {
        return '{{' . $this->renderChildren($node) . '}}';
    }

    protected function renderSuperscript(ElementNode $node): string
    {
        return '^^' . $this->renderChildren($node) . '^^';
    }

    protected function renderSubscript(ElementNode $node): string
    {
        return ',,' . $this->renderChildren($node) . ',,';
    }

    protected function renderColor(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $color = $attrs['color'] ?? '';

        // Strip leading # for hex colors to match tokenizer input format
        if (str_starts_with($color, '#') && preg_match('/^#[0-9A-Fa-f]{3,6}$/', $color)) {
            $color = substr($color, 1);
        }

        return '##' . $color . '|' . $this->renderChildren($node) . '##';
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

        // If no href attribute, try to get from text content
        if ($href === '') {
            $children = $node->getChildren();
            if (count($children) === 1 && $children[0] instanceof TextNode) {
                $href = $children[0]->getText();
            }
        }

        if ($text !== '' && $text !== $href) {
            return '[' . $href . ' ' . $text . ']';
        }

        return '[' . $href . ']';
    }

    protected function renderWikilink(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $page = $attrs['page'] ?? $this->renderChildren($node);

        return '((' . $page . '))';
    }

    protected function renderPhplookup(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $function = $attrs['function'] ?? $this->renderChildren($node);

        return '[[php ' . $function . ']]';
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
        return '----';
    }

    protected function renderCode(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $content = $this->renderChildren($node);

        $tag = '<code';
        if (isset($attrs['language']) && $attrs['language'] !== '') {
            $tag .= ' type="' . $attrs['language'] . '"';
        }
        $tag .= '>';

        return $tag . "\n" . $content . "\n</code>";
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
        return '= ' . $this->renderChildren($node);
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
    // Tables (original PEAR format: || for all cells, ~ prefix for headers)
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
        $cells = [];
        foreach ($node->getChildren() as $child) {
            if ($child instanceof ElementNode && $child->getName() === 'cell') {
                $attrs = $child->getAttributes();
                $content = $this->renderChildren($child);
                $prefix = '';

                if (($attrs['type'] ?? 'data') === 'header') {
                    $prefix = '~ ';
                } elseif (isset($attrs['align'])) {
                    $prefix = match ($attrs['align']) {
                        'right'  => '> ',
                        'center' => '= ',
                        'left'   => '< ',
                        default  => '',
                    };
                }

                $cells[] = $prefix . $content;
            }
        }

        return '|| ' . implode(' || ', $cells) . ' ||';
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
                    $i++; // Skip the defdef
                }

                $lines[] = ': ' . $term . ' : ' . $def;
            }
        }

        return implode("\n", $lines);
    }

    // ---------------------------------------------------------------
    // Miscellaneous self-closing / special elements
    // ---------------------------------------------------------------

    protected function renderAnchor(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $name = $attrs['name'] ?? '';

        return '[[# ' . $name . ']]';
    }

    protected function renderToc(ElementNode $node): string
    {
        return '[[toc]]';
    }

    protected function renderImage(ElementNode $node): string
    {
        $attrs = $node->getAttributes();

        if (isset($attrs['src'])) {
            return '[[image ' . $attrs['src'] . ']]';
        }

        // Fallback: try to get URL from text content
        $children = $node->getChildren();
        if (count($children) === 1 && $children[0] instanceof TextNode) {
            return '[[image ' . $children[0]->getText() . ']]';
        }

        return '[[image]]';
    }

    // ---------------------------------------------------------------
    // Revise (standalone ins — del is handled in renderNodeList)
    // ---------------------------------------------------------------

    protected function renderReviseDel(ElementNode $node): string
    {
        // Called from visitElement dispatch — standalone case
        return '@@---' . $this->renderChildren($node) . '@@';
    }

    protected function renderReviseIns(ElementNode $node): string
    {
        // Called from visitElement dispatch — standalone case
        return '@@+++' . $this->renderChildren($node) . '@@';
    }
}
