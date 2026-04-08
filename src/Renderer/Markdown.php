<?php

declare(strict_types=1);

/**
 * Copyright 2025-2026 The Horde Project (http://www.horde.org/)
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
 * Markdown (CommonMark / GFM) renderer for typed AST
 *
 * Renders any typed document tree to CommonMark-compatible Markdown text.
 * GFM extensions (tables, strikethrough) are emitted when the AST contains
 * them. Designed for idempotency: parse -> render -> parse -> render stabilizes.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class Markdown implements Renderer, NodeVisitor
{
    /** @var array<string, callable(ElementNode, NodeVisitor): string> */
    private array $elementHandlers = [];

    private const BLOCK_ELEMENTS = [
        'heading', 'horiz', 'code', 'raw', 'preformatted',
        'blockquote', 'paragraph', 'table', 'list', 'deflist',
        'htmlblock', 'image',
    ];

    private int $listDepth = 0;

    /** @var array<string> Stack of 'bullet'|'ordered' per nesting level */
    private array $listTypeStack = [];

    /** @var array<int> Stack of item indices per nesting level */
    private array $listItemIndex = [];

    /** @var array<int> Stack of list start numbers per nesting level */
    private array $listStartStack = [];

    /** Whether we are inside a verbatim context (code, htmlblock, htmlinline) */
    private bool $inVerbatim = false;

    public function setElementHandler(string $tagName, callable $handler): void
    {
        $this->elementHandlers[strtolower($tagName)] = $handler;
    }

    public function render(DocumentNode $document): string
    {
        $this->listDepth = 0;
        $this->listTypeStack = [];
        $this->listItemIndex = [];
        $this->listStartStack = [];
        $this->inVerbatim = false;

        return $this->visitDocument($document);
    }

    public function getFormat(): string
    {
        return 'markdown';
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
        $text = $node->getText();

        if ($this->inVerbatim) {
            return $text;
        }

        return $this->escapeMarkdown($text);
    }

    // ---------------------------------------------------------------
    // Text escaping
    // ---------------------------------------------------------------

    /**
     * Escape CommonMark-significant ASCII punctuation
     *
     * Over-escaping is acceptable — it guarantees idempotency since
     * \X -> parser -> TextNode(X) -> renderer -> \X is stable.
     */
    private function escapeMarkdown(string $text): string
    {
        // Backslash-escape all ASCII punctuation that CommonMark treats specially
        return preg_replace('/([\\\\*_\[\]{}()#+\-.!|~<>&`])/', '\\\\$1', $text);
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

    /**
     * Render children without escaping (for verbatim contexts)
     */
    private function renderChildrenRaw(ElementNode $node): string
    {
        $output = '';
        foreach ($node->getChildren() as $child) {
            if ($child instanceof TextNode) {
                $output .= $child->getText();
            } else {
                $output .= $child->accept($this);
            }
        }
        return $output;
    }

    // ---------------------------------------------------------------
    // Block elements
    // ---------------------------------------------------------------

    protected function renderHeading(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $level = max(1, min(6, (int) ($attrs['level'] ?? 1)));

        return str_repeat('#', $level) . ' ' . $this->renderChildren($node);
    }

    protected function renderParagraph(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }

    protected function renderCode(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $language = $attrs['language'] ?? ($attrs['type'] ?? '');

        $prevVerbatim = $this->inVerbatim;
        $this->inVerbatim = true;
        $content = $this->renderChildrenRaw($node);
        $this->inVerbatim = $prevVerbatim;

        // Remove trailing newline from content (fence provides its own)
        $content = rtrim($content, "\n");

        // Choose fence length: at least 3 backticks, more if content contains backticks
        $fenceLen = 3;
        if (preg_match('/`{3,}/', $content, $m)) {
            $fenceLen = max($fenceLen, strlen($m[0]) + 1);
        }
        $fence = str_repeat('`', $fenceLen);

        return $fence . $language . "\n" . $content . "\n" . $fence;
    }

    protected function renderPreformatted(ElementNode $node): string
    {
        return $this->renderCode($node);
    }

    protected function renderRaw(ElementNode $node): string
    {
        return $this->renderCode($node);
    }

    protected function renderBlockquote(ElementNode $node): string
    {
        $content = $this->renderChildren($node);
        $lines = explode("\n", $content);

        $output = [];
        foreach ($lines as $line) {
            if ($line === '') {
                $output[] = '>';
            } else {
                $output[] = '> ' . $line;
            }
        }

        return implode("\n", $output);
    }

    protected function renderHoriz(ElementNode $node): string
    {
        return '---';
    }

    protected function renderHtmlblock(ElementNode $node): string
    {
        $prevVerbatim = $this->inVerbatim;
        $this->inVerbatim = true;
        $content = $this->renderChildrenRaw($node);
        $this->inVerbatim = $prevVerbatim;

        return rtrim($content, "\n");
    }

    // ---------------------------------------------------------------
    // Inline elements
    // ---------------------------------------------------------------

    protected function renderBold(ElementNode $node): string
    {
        return '**' . $this->renderChildren($node) . '**';
    }

    protected function renderStrong(ElementNode $node): string
    {
        return '**' . $this->renderChildren($node) . '**';
    }

    protected function renderItalic(ElementNode $node): string
    {
        return '*' . $this->renderChildren($node) . '*';
    }

    protected function renderEmphasis(ElementNode $node): string
    {
        return '*' . $this->renderChildren($node) . '*';
    }

    protected function renderStrike(ElementNode $node): string
    {
        return '~~' . $this->renderChildren($node) . '~~';
    }

    protected function renderUrl(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $href = $attrs['href'] ?? '';
        $title = $attrs['title'] ?? '';
        $text = $this->renderChildren($node);

        if ($href === '') {
            $children = $node->getChildren();
            if (count($children) === 1 && $children[0] instanceof TextNode) {
                $href = $children[0]->getText();
            }
        }

        if ($title !== '') {
            return '[' . $text . '](' . $href . ' "' . $title . '")';
        }

        return '[' . $text . '](' . $href . ')';
    }

    protected function renderImage(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $src = $attrs['src'] ?? '';
        $alt = $attrs['alt'] ?? '';
        $title = $attrs['title'] ?? '';

        if ($title !== '') {
            return '![' . $alt . '](' . $src . ' "' . $title . '")';
        }

        return '![' . $alt . '](' . $src . ')';
    }

    protected function renderHtmlinline(ElementNode $node): string
    {
        $prevVerbatim = $this->inVerbatim;
        $this->inVerbatim = true;
        $content = $this->renderChildrenRaw($node);
        $this->inVerbatim = $prevVerbatim;

        return $content;
    }

    protected function renderBreak(ElementNode $node): string
    {
        return "  \n";
    }

    protected function renderSoftbreak(ElementNode $node): string
    {
        return "\n";
    }

    // ---------------------------------------------------------------
    // Lists
    // ---------------------------------------------------------------

    protected function renderList(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $type = $attrs['type'] ?? 'bullet';
        $start = (int) ($attrs['start'] ?? 1);

        // Normalize type names
        $listType = match ($type) {
            '1', 'number', 'ordered' => 'ordered',
            default => 'bullet',
        };

        $this->listDepth++;
        $this->listTypeStack[] = $listType;
        $this->listStartStack[] = $start;
        $this->listItemIndex[] = 0;

        $output = $this->renderListChildren($node);

        array_pop($this->listTypeStack);
        array_pop($this->listStartStack);
        array_pop($this->listItemIndex);
        $this->listDepth--;

        return $output;
    }

    protected function renderListitem(ElementNode $node): string
    {
        $depthIdx = $this->listDepth - 1;
        $type = $this->listTypeStack[$depthIdx] ?? 'bullet';
        $start = $this->listStartStack[$depthIdx] ?? 1;
        $index = $this->listItemIndex[$depthIdx] ?? 0;
        $this->listItemIndex[$depthIdx] = $index + 1;

        $tight = $node->getAttributes()['tight'] ?? false;

        if ($type === 'ordered') {
            $num = $start + $index;
            $marker = $num . '. ';
        } else {
            $marker = '- ';
        }

        $markerWidth = strlen($marker);
        $indent = str_repeat(' ', $markerWidth);

        // Render children
        $content = '';
        $childParts = [];
        foreach ($node->getChildren() as $child) {
            if ($child instanceof ElementNode && $child->getName() === 'list') {
                $childParts[] = ['block' => true, 'text' => $child->accept($this)];
            } else {
                $childParts[] = ['block' => false, 'text' => $child->accept($this)];
            }
        }

        // Build content
        $firstLine = true;
        foreach ($childParts as $part) {
            if ($part['block']) {
                // Sub-list: indent all lines
                $content = rtrim($content) . "\n";
                $lines = explode("\n", $part['text']);
                foreach ($lines as $line) {
                    if ($line === '') {
                        $content .= "\n";
                    } else {
                        $content .= $indent . $line . "\n";
                    }
                }
                $content = rtrim($content, "\n");
            } else {
                if ($firstLine) {
                    $content .= $part['text'];
                    $firstLine = false;
                } else {
                    // Continuation lines: indent
                    $lines = explode("\n", $part['text']);
                    foreach ($lines as $j => $line) {
                        if ($j > 0) {
                            $content .= "\n";
                            if ($line !== '') {
                                $content .= $indent;
                            }
                        }
                        $content .= $line;
                    }
                }
            }
        }

        $result = $marker . $content;

        // Loose lists: add blank line after item
        if (!$tight) {
            $result .= "\n";
        }

        return $result;
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
    // Tables (GFM pipe table format)
    // ---------------------------------------------------------------

    protected function renderTable(ElementNode $node): string
    {
        $rows = [];
        foreach ($node->getChildren() as $child) {
            if ($child instanceof ElementNode && $child->getName() === 'row') {
                $rows[] = $child;
            }
        }

        if (empty($rows)) {
            return '';
        }

        $output = '';

        // First row is the header
        $headerRow = $rows[0];
        $headerCells = $this->getRowCells($headerRow);
        $output .= '| ' . implode(' | ', $headerCells) . " |\n";

        // Delimiter row with alignment
        $delimiters = [];
        foreach ($headerRow->getChildren() as $child) {
            if ($child instanceof ElementNode && $child->getName() === 'cell') {
                $align = $child->getAttributes()['align'] ?? '';
                $delimiters[] = match ($align) {
                    'left' => ':---',
                    'center' => ':---:',
                    'right' => '---:',
                    default => '---',
                };
            }
        }
        $output .= '| ' . implode(' | ', $delimiters) . " |\n";

        // Data rows
        for ($i = 1; $i < count($rows); $i++) {
            $cells = $this->getRowCells($rows[$i]);
            $output .= '| ' . implode(' | ', $cells) . " |\n";
        }

        return rtrim($output, "\n");
    }

    private function getRowCells(ElementNode $row): array
    {
        $cells = [];
        foreach ($row->getChildren() as $child) {
            if ($child instanceof ElementNode && $child->getName() === 'cell') {
                $cells[] = $this->renderChildren($child);
            }
        }
        return $cells;
    }

    // ---------------------------------------------------------------
    // Fallback mappings (no native Markdown syntax)
    // ---------------------------------------------------------------

    protected function renderUnderline(ElementNode $node): string
    {
        return '<u>' . $this->renderChildren($node) . '</u>';
    }

    protected function renderSuperscript(ElementNode $node): string
    {
        return '<sup>' . $this->renderChildren($node) . '</sup>';
    }

    protected function renderSubscript(ElementNode $node): string
    {
        return '<sub>' . $this->renderChildren($node) . '</sub>';
    }

    protected function renderTt(ElementNode $node): string
    {
        $prevVerbatim = $this->inVerbatim;
        $this->inVerbatim = true;
        $content = $this->renderChildrenRaw($node);
        $this->inVerbatim = $prevVerbatim;

        // Choose backtick count to avoid conflicts with content
        $backtickLen = 1;
        if (preg_match_all('/`+/', $content, $matches)) {
            foreach ($matches[0] as $run) {
                if (strlen($run) >= $backtickLen) {
                    $backtickLen = strlen($run) + 1;
                }
            }
        }
        $ticks = str_repeat('`', $backtickLen);

        // Space-pad when content starts or ends with backtick
        if (str_starts_with($content, '`') || str_ends_with($content, '`')) {
            return $ticks . ' ' . $content . ' ' . $ticks;
        }

        return $ticks . $content . $ticks;
    }

    protected function renderDel(ElementNode $node): string
    {
        return '<del>' . $this->renderChildren($node) . '</del>';
    }

    protected function renderIns(ElementNode $node): string
    {
        return '<ins>' . $this->renderChildren($node) . '</ins>';
    }

    protected function renderReviseDel(ElementNode $node): string
    {
        return '<del>' . $this->renderChildren($node) . '</del>';
    }

    protected function renderReviseIns(ElementNode $node): string
    {
        return '<ins>' . $this->renderChildren($node) . '</ins>';
    }

    protected function renderEmail(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $email = $attrs['email'] ?? '';
        $text = $this->renderChildren($node);

        return '[' . $text . '](mailto:' . $email . ')';
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

        if ($text !== '' && $text !== $target) {
            return '[' . $text . '](' . $target . ')';
        }

        return '[' . $target . '](' . $target . ')';
    }

    protected function renderPhplookup(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $function = $attrs['function'] ?? '';

        return '[' . $function . '](https://www.php.net/' . $function . ')';
    }

    protected function renderYoutube(ElementNode $node): string
    {
        $children = $node->getChildren();
        if (count($children) === 1 && $children[0] instanceof TextNode) {
            $videoId = trim($children[0]->getText());
            $url = 'https://www.youtube.com/watch?v=' . $videoId;
            return '[video](' . $url . ')';
        }
        return '';
    }

    protected function renderAnchor(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $name = $attrs['name'] ?? '';

        return '<a id="' . $name . '"></a>';
    }

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

                if ($i + 1 < $count
                    && $children[$i + 1] instanceof ElementNode
                    && $children[$i + 1]->getName() === 'defdef') {
                    $def = $this->renderChildren($children[$i + 1]);
                    $i++;
                }

                $lines[] = '**' . $term . '**: ' . $def;
            }
        }

        return implode("\n", $lines);
    }

    // Alignment/styling wrappers — strip, just render children
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

    protected function renderToc(ElementNode $node): string
    {
        return '';
    }
}
