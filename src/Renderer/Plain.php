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
 * Plain text renderer for typed AST
 *
 * Strips all formatting markup and produces readable plain text output.
 * Preserves document structure through whitespace (newlines, indentation).
 * Handles AST nodes from all parser dialects (Yawiki, BBCode, Cowiki).
 *
 * Useful for search indexing, notifications, excerpts, and accessibility.
 *
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class Plain implements Renderer, NodeVisitor
{
    /** @var array<string, callable(ElementNode, NodeVisitor): string> */
    private array $elementHandlers = [];

    private const BLOCK_ELEMENTS = [
        'heading', 'horiz', 'code', 'raw', 'blockquote',
        'paragraph', 'table', 'list', 'deflist', 'center',
        'left', 'right', 'justify', 'youtube',
    ];

    private int $listDepth = 0;
    private int $blockquoteDepth = 0;

    public function setElementHandler(string $tagName, callable $handler): void
    {
        $this->elementHandlers[strtolower($tagName)] = $handler;
    }

    public function render(DocumentNode $document): string
    {
        $this->listDepth = 0;
        $this->blockquoteDepth = 0;

        return $this->visitDocument($document);
    }

    public function getFormat(): string
    {
        return 'plain';
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

    public function renderChildren(ElementNode $node): string
    {
        return $this->renderNodeList($node->getChildren());
    }

    // ---------------------------------------------------------------
    // Inline formatting — strip markup, pass through text
    // ---------------------------------------------------------------

    protected function renderBold(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }

    protected function renderItalic(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }

    protected function renderUnderline(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }

    protected function renderTt(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }

    protected function renderStrong(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }

    protected function renderEmphasis(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }

    protected function renderStrike(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }

    protected function renderSuperscript(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }

    protected function renderSubscript(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }

    // ---------------------------------------------------------------
    // Styling — strip, pass through text
    // ---------------------------------------------------------------

    protected function renderColor(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }

    protected function renderFont(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }

    protected function renderSize(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }

    protected function renderColortext(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }

    // ---------------------------------------------------------------
    // Alignment — strip, pass through text
    // ---------------------------------------------------------------

    protected function renderCenter(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }

    protected function renderLeft(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }

    protected function renderRight(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }

    protected function renderJustify(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }

    // ---------------------------------------------------------------
    // Block elements
    // ---------------------------------------------------------------

    protected function renderHeading(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }

    protected function renderHoriz(ElementNode $node): string
    {
        return '';
    }

    protected function renderCode(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }

    protected function renderRaw(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }

    protected function renderBlockquote(ElementNode $node): string
    {
        $this->blockquoteDepth++;
        $content = $this->renderChildren($node);
        $this->blockquoteDepth--;

        $indent = str_repeat('    ', $this->blockquoteDepth + 1);
        $lines = explode("\n", $content);
        $indented = [];
        foreach ($lines as $line) {
            $indented[] = $indent . $line;
        }

        return implode("\n", $indented);
    }

    protected function renderParagraph(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }

    // ---------------------------------------------------------------
    // Links — show text with URL context
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
            return $text . ' (' . $href . ')';
        }

        return $href !== '' ? $href : $text;
    }

    protected function renderEmail(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $email = $attrs['email'] ?? '';
        $text = $this->renderChildren($node);

        if ($text !== '' && $text !== $email) {
            return $text . ' (' . $email . ')';
        }

        return $email !== '' ? $email : $text;
    }

    protected function renderWikilink(ElementNode $node): string
    {
        $text = $this->renderChildren($node);
        if ($text !== '') {
            return $text;
        }

        $attrs = $node->getAttributes();
        return $attrs['page'] ?? '';
    }

    protected function renderFreelink(ElementNode $node): string
    {
        $text = $this->renderChildren($node);
        if ($text !== '') {
            return $text;
        }

        $attrs = $node->getAttributes();
        return $attrs['page'] ?? '';
    }

    protected function renderPhplookup(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        return $attrs['function'] ?? $this->renderChildren($node);
    }

    // ---------------------------------------------------------------
    // Lists
    // ---------------------------------------------------------------

    protected function renderList(ElementNode $node): string
    {
        $this->listDepth++;
        $output = $this->renderListChildren($node);
        $this->listDepth--;

        return $output;
    }

    protected function renderListitem(ElementNode $node): string
    {
        $indent = str_repeat('    ', $this->listDepth - 1);

        $content = '';
        foreach ($node->getChildren() as $child) {
            $rendered = $child->accept($this);
            if ($child instanceof ElementNode && $child->getName() === 'list') {
                $content = rtrim($content) . "\n" . $rendered;
            } else {
                $content .= $rendered;
            }
        }

        return $indent . '- ' . $content;
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
    // Tables — pipe-delimited plain text
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
                $cells[] = $this->renderChildren($child);
            }
        }
        return '|| ' . implode(' || ', $cells) . ' ||';
    }

    // ---------------------------------------------------------------
    // Definition lists
    // ---------------------------------------------------------------

    protected function renderDeflist(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }

    protected function renderDefterm(ElementNode $node): string
    {
        return '    ' . $this->renderChildren($node);
    }

    protected function renderDefdef(ElementNode $node): string
    {
        return '        ' . $this->renderChildren($node);
    }

    // ---------------------------------------------------------------
    // Revision marks — strip, pass through text
    // ---------------------------------------------------------------

    protected function renderReviseDel(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }

    protected function renderReviseIns(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }

    // ---------------------------------------------------------------
    // Suppressed elements — produce no output
    // ---------------------------------------------------------------

    protected function renderImage(ElementNode $node): string
    {
        return '';
    }

    protected function renderYoutube(ElementNode $node): string
    {
        return '';
    }

    protected function renderToc(ElementNode $node): string
    {
        return '';
    }

    protected function renderAnchor(ElementNode $node): string
    {
        return '';
    }
}
