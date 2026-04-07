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
 * MediaWiki markup renderer for typed AST
 *
 * Renders the typed document tree back to MediaWiki wikitext.
 * Designed for idempotency: parse -> render -> parse -> render stabilizes.
 *
 * MediaWiki core syntax:
 * - '''bold''', ''italic'', '''''bold italic'''''
 * - Headings: = H1 = to ====== H6 ====== (NOT inverted)
 * - Links: [[Page|text]], [http://url text]
 * - Images: [[File:src|alt]]
 * - Lists: * bullet, # numbered (character repetition for nesting)
 * - Tables: {| |- | ! |}
 * - Code: <code>...</code>, <pre>...</pre>
 * - Nowiki: <nowiki>...</nowiki>
 * - HTML subset: <sup>, <sub>, <u>, <tt>, <s>, <del>, <ins>, <br />
 * - Break: <br />, horiz: ----
 * - Blockquote: <blockquote>...</blockquote>
 * - Alignment: <div style="text-align:X;">
 * - Styling: <span style="color:X;">, <span style="font-family:X;">
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class Mediawiki implements Renderer, NodeVisitor
{
    private const BLOCK_ELEMENTS = [
        'heading', 'horiz', 'code', 'raw', 'blockquote',
        'paragraph', 'table', 'list', 'deflist', 'center',
        'left', 'right', 'justify', 'preformatted', 'image',
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
        return 'mediawiki';
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

    protected function renderChildren(ElementNode $node): string
    {
        return $this->renderNodeList($node->getChildren());
    }

    // ---------------------------------------------------------------
    // Inline formatting — apostrophe state machine
    // ---------------------------------------------------------------

    protected function renderBold(ElementNode $node): string
    {
        return "'''" . $this->renderChildren($node) . "'''";
    }

    protected function renderStrong(ElementNode $node): string
    {
        return "'''" . $this->renderChildren($node) . "'''";
    }

    protected function renderItalic(ElementNode $node): string
    {
        return "''" . $this->renderChildren($node) . "''";
    }

    protected function renderEmphasis(ElementNode $node): string
    {
        return "''" . $this->renderChildren($node) . "''";
    }

    protected function renderUnderline(ElementNode $node): string
    {
        return '<u>' . $this->renderChildren($node) . '</u>';
    }

    protected function renderTt(ElementNode $node): string
    {
        return '<tt>' . $this->renderChildren($node) . '</tt>';
    }

    protected function renderSuperscript(ElementNode $node): string
    {
        return '<sup>' . $this->renderChildren($node) . '</sup>';
    }

    protected function renderSubscript(ElementNode $node): string
    {
        return '<sub>' . $this->renderChildren($node) . '</sub>';
    }

    protected function renderStrike(ElementNode $node): string
    {
        return '<s>' . $this->renderChildren($node) . '</s>';
    }

    protected function renderDel(ElementNode $node): string
    {
        return '<del>' . $this->renderChildren($node) . '</del>';
    }

    protected function renderIns(ElementNode $node): string
    {
        return '<ins>' . $this->renderChildren($node) . '</ins>';
    }

    protected function renderBreak(ElementNode $node): string
    {
        return '<br />';
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
            return '[' . $href . ' ' . $text . ']';
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

    protected function renderFreelink(ElementNode $node): string
    {
        return $this->renderWikilink($node);
    }

    protected function renderEmail(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $email = $attrs['email'] ?? '';
        $text = $this->renderChildren($node);

        if ($text !== '' && $text !== $email) {
            return '[[mailto:' . $email . '|' . $text . ']]';
        }

        return '[[mailto:' . $email . ']]';
    }

    protected function renderImage(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $src = $attrs['src'] ?? '';
        $alt = $attrs['alt'] ?? '';

        if ($alt !== '') {
            return '[[File:' . $src . '|' . $alt . ']]';
        }

        return '[[File:' . $src . ']]';
    }

    // ---------------------------------------------------------------
    // Block elements
    // ---------------------------------------------------------------

    protected function renderHeading(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $level = max(1, min(6, (int) ($attrs['level'] ?? 1)));

        $markers = str_repeat('=', $level);

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
        $lang = $attrs['language'] ?? ($attrs['type'] ?? '');

        if ($lang !== '') {
            return "<code " . $lang . ">\n" . $content . "\n</code>";
        }

        return "<code>\n" . $content . "\n</code>";
    }

    protected function renderPreformatted(ElementNode $node): string
    {
        $content = $this->renderChildren($node);

        return "<pre>\n" . $content . "\n</pre>";
    }

    protected function renderRaw(ElementNode $node): string
    {
        $content = $this->renderChildren($node);

        return "<nowiki>" . $content . "</nowiki>";
    }

    protected function renderBlockquote(ElementNode $node): string
    {
        return '<blockquote>' . $this->renderChildren($node) . '</blockquote>';
    }

    protected function renderParagraph(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }

    // ---------------------------------------------------------------
    // Alignment — <div style="text-align:X;">
    // ---------------------------------------------------------------

    protected function renderCenter(ElementNode $node): string
    {
        return '<div style="text-align:center;">' . $this->renderChildren($node) . '</div>';
    }

    protected function renderLeft(ElementNode $node): string
    {
        return '<div style="text-align:left;">' . $this->renderChildren($node) . '</div>';
    }

    protected function renderRight(ElementNode $node): string
    {
        return '<div style="text-align:right;">' . $this->renderChildren($node) . '</div>';
    }

    protected function renderJustify(ElementNode $node): string
    {
        return '<div style="text-align:justify;">' . $this->renderChildren($node) . '</div>';
    }

    // ---------------------------------------------------------------
    // Styling — <span style="...">
    // ---------------------------------------------------------------

    protected function renderColor(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $color = $attrs['color'] ?? '';

        return '<span style="color:' . $color . ';">' . $this->renderChildren($node) . '</span>';
    }

    protected function renderFont(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $font = $attrs['font'] ?? '';

        return '<span style="font-family:' . $font . ';">' . $this->renderChildren($node) . '</span>';
    }

    protected function renderSize(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $size = $attrs['size'] ?? '';

        return '<span style="font-size:' . $size . ';">' . $this->renderChildren($node) . '</span>';
    }

    protected function renderColortext(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $color = $attrs['color'] ?? '';

        return '<span style="color:' . $color . ';">' . $this->renderChildren($node) . '</span>';
    }

    // ---------------------------------------------------------------
    // Lists — character repetition: *, #, **, ##, *#, etc.
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
        // Build prefix from the type stack
        $prefix = '';
        foreach ($this->listTypeStack as $type) {
            $prefix .= ($type === 'number' || $type === '1') ? '#' : '*';
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

        return $prefix . ' ' . $content;
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
    // Tables — {| |- | ! |}
    // ---------------------------------------------------------------

    protected function renderTable(ElementNode $node): string
    {
        $output = "{|\n";

        $rows = [];
        foreach ($node->getChildren() as $child) {
            if ($child instanceof ElementNode && $child->getName() === 'row') {
                $rows[] = $this->renderRow($child);
            }
        }

        $output .= implode("|-\n", $rows);
        $output .= "|}";

        return $output;
    }

    protected function renderRow(ElementNode $node): string
    {
        $cells = [];
        $isHeaderRow = true;

        foreach ($node->getChildren() as $child) {
            if ($child instanceof ElementNode && $child->getName() === 'cell') {
                $attrs = $child->getAttributes();
                $isHeader = (($attrs['type'] ?? 'data') === 'header');
                if (!$isHeader) {
                    $isHeaderRow = false;
                }
                $cells[] = ['header' => $isHeader, 'content' => $this->renderChildren($child)];
            }
        }

        if (empty($cells)) {
            return '';
        }

        if ($isHeaderRow) {
            // Header row: ! cell1 !! cell2
            $parts = array_map(fn($c) => $c['content'], $cells);
            return '! ' . implode(' !! ', $parts) . "\n";
        }

        // Data row: | cell1 || cell2
        $parts = array_map(fn($c) => $c['content'], $cells);
        return '| ' . implode(' || ', $parts) . "\n";
    }

    // ---------------------------------------------------------------
    // Definition lists — ; term / : definition
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

                $lines[] = '; ' . $term . ' : ' . $def;
            }
        }

        return implode("\n", $lines);
    }

    // ---------------------------------------------------------------
    // Revision marks
    // ---------------------------------------------------------------

    protected function renderReviseDel(ElementNode $node): string
    {
        return '<del>' . $this->renderChildren($node) . '</del>';
    }

    protected function renderReviseIns(ElementNode $node): string
    {
        return '<ins>' . $this->renderChildren($node) . '</ins>';
    }

    // ---------------------------------------------------------------
    // Suppressed / metadata elements
    // ---------------------------------------------------------------

    protected function renderYoutube(ElementNode $node): string
    {
        $children = $node->getChildren();
        if (count($children) === 1 && $children[0] instanceof TextNode) {
            $videoId = trim($children[0]->getText());
            return 'https://www.youtube.com/watch?v=' . $videoId;
        }
        return '';
    }

    protected function renderToc(ElementNode $node): string
    {
        return '__TOC__';
    }

    protected function renderAnchor(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $name = $attrs['name'] ?? '';

        return '<span id="' . $name . '"></span>';
    }

    protected function renderPhplookup(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $function = $attrs['function'] ?? '';

        return '[[php ' . $function . ']]';
    }
}
