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
 * BBCode markup renderer for typed AST
 *
 * Renders the typed document tree back to BBCode markup.
 * Designed for idempotency: parse → render → parse → render stabilizes.
 *
 * @author   Bertrand Gugger <bertrand@toggg.com>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class BBCode implements Renderer, NodeVisitor
{
    /** @var array<string, callable(ElementNode, NodeVisitor): string> */
    private array $elementHandlers = [];

    private const BLOCK_ELEMENTS = [
        'blockquote', 'code', 'horiz', 'center', 'left', 'right',
        'justify', 'list', 'youtube',
    ];

    private int $listDepth = 0;

    /** @var array<string> Stack of list types per nesting level */
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
        return 'bbcode';
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

    public function renderChildren(ElementNode $node): string
    {
        return $this->renderNodeList($node->getChildren());
    }

    // ---------------------------------------------------------------
    // Inline formatting
    // ---------------------------------------------------------------

    protected function renderBold(ElementNode $node): string
    {
        return '[b]' . $this->renderChildren($node) . '[/b]';
    }

    protected function renderItalic(ElementNode $node): string
    {
        return '[i]' . $this->renderChildren($node) . '[/i]';
    }

    protected function renderUnderline(ElementNode $node): string
    {
        return '[u]' . $this->renderChildren($node) . '[/u]';
    }

    protected function renderStrike(ElementNode $node): string
    {
        return '[s]' . $this->renderChildren($node) . '[/s]';
    }

    protected function renderSuperscript(ElementNode $node): string
    {
        return '[sup]' . $this->renderChildren($node) . '[/sup]';
    }

    protected function renderSubscript(ElementNode $node): string
    {
        return '[sub]' . $this->renderChildren($node) . '[/sub]';
    }

    // ---------------------------------------------------------------
    // Links and media
    // ---------------------------------------------------------------

    protected function renderUrl(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $href = $attrs['href'] ?? '';
        $text = $this->renderChildren($node);

        // [url=href]text[/url] when href differs from text
        if ($href !== '' && $text !== $href) {
            return '[url=' . $href . ']' . $text . '[/url]';
        }

        // [url]href[/url] when href matches text or no separate href
        $url = $href !== '' ? $href : $text;
        return '[url]' . $url . '[/url]';
    }

    protected function renderEmail(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $email = $attrs['email'] ?? '';
        $text = $this->renderChildren($node);

        // [email=addr]text[/email] when email differs from text
        if ($email !== '' && $text !== $email) {
            return '[email=' . $email . ']' . $text . '[/email]';
        }

        // [email]addr[/email] when email matches text or no separate email
        $addr = $email !== '' ? $email : $text;
        return '[email]' . $addr . '[/email]';
    }

    protected function renderImage(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $content = $this->renderChildren($node);

        if (isset($attrs['alt']) && $attrs['alt'] !== '') {
            return '[img alt="' . $attrs['alt'] . '"]' . $content . '[/img]';
        }

        return '[img]' . $content . '[/img]';
    }

    protected function renderYoutube(ElementNode $node): string
    {
        return '[youtube]' . $this->renderChildren($node) . '[/youtube]';
    }

    // ---------------------------------------------------------------
    // Block elements
    // ---------------------------------------------------------------

    protected function renderBlockquote(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $content = $this->renderChildren($node);

        if (isset($attrs['author']) && $attrs['author'] !== '') {
            return '[quote=' . $attrs['author'] . ']' . $content . '[/quote]';
        }

        return '[quote]' . $content . '[/quote]';
    }

    protected function renderCode(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $content = $this->renderChildren($node);

        if (isset($attrs['language']) && $attrs['language'] !== '') {
            return '[code=' . $attrs['language'] . ']' . $content . '[/code]';
        }

        return '[code]' . $content . '[/code]';
    }

    protected function renderHoriz(ElementNode $node): string
    {
        return '[hr]';
    }

    // ---------------------------------------------------------------
    // Alignment
    // ---------------------------------------------------------------

    protected function renderCenter(ElementNode $node): string
    {
        return '[center]' . $this->renderChildren($node) . '[/center]';
    }

    protected function renderLeft(ElementNode $node): string
    {
        return '[left]' . $this->renderChildren($node) . '[/left]';
    }

    protected function renderRight(ElementNode $node): string
    {
        return '[right]' . $this->renderChildren($node) . '[/right]';
    }

    protected function renderJustify(ElementNode $node): string
    {
        return '[justify]' . $this->renderChildren($node) . '[/justify]';
    }

    // ---------------------------------------------------------------
    // Lists
    // ---------------------------------------------------------------

    protected function renderList(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $type = $attrs['type'] ?? '';

        $this->listDepth++;
        $this->listTypeStack[] = $type;

        $content = $this->renderListChildren($node);

        array_pop($this->listTypeStack);
        $this->listDepth--;

        if ($type !== '') {
            return '[list=' . $type . ']' . $content . '[/list]';
        }

        return '[list]' . $content . '[/list]';
    }

    protected function renderListitem(ElementNode $node): string
    {
        $content = '';
        foreach ($node->getChildren() as $child) {
            $rendered = $child->accept($this);
            if ($child instanceof ElementNode && $child->getName() === 'list') {
                // Sublist: continue on next line
                $content = rtrim($content) . "\n" . $rendered;
            } else {
                $content .= $rendered;
            }
        }

        return '[*]' . $content;
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
        return "\n" . implode("\n", $items) . "\n";
    }

    // ---------------------------------------------------------------
    // Styling
    // ---------------------------------------------------------------

    protected function renderColor(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $color = $attrs['color'] ?? '';

        return '[color=' . $color . ']' . $this->renderChildren($node) . '[/color]';
    }

    protected function renderFont(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $font = $attrs['font'] ?? '';

        return '[font=' . $font . ']' . $this->renderChildren($node) . '[/font]';
    }

    protected function renderSize(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $size = $attrs['size'] ?? '';

        return '[size=' . $size . ']' . $this->renderChildren($node) . '[/size]';
    }

    // ---------------------------------------------------------------
    // Paragraph (no BBCode wrapper)
    // ---------------------------------------------------------------

    protected function renderParagraph(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }
}
