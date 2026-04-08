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
 * DocBook XML renderer for typed AST
 *
 * Renders the typed document tree to DocBook 5 XML. Uses standard DocBook
 * elements: article, section, para, emphasis, itemizedlist, orderedlist,
 * programlisting, glosslist, etc.
 *
 * Text nodes are XML-escaped. Block elements produce proper DocBook structure.
 * Section nesting is tracked to close open sections at the end.
 *
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class Docbook implements Renderer, NodeVisitor
{
    /** @var array<string, callable(ElementNode, NodeVisitor): string> */
    private array $elementHandlers = [];

    private const BLOCK_ELEMENTS = [
        'heading', 'horiz', 'code', 'raw', 'blockquote',
        'paragraph', 'table', 'list', 'deflist', 'center',
        'left', 'right', 'justify', 'preformatted',
    ];

    /** @var array<int> Stack of open section levels for proper nesting */
    private array $sectionStack = [];

    public function setElementHandler(string $tagName, callable $handler): void
    {
        $this->elementHandlers[strtolower($tagName)] = $handler;
    }

    public function render(DocumentNode $document): string
    {
        $this->sectionStack = [];

        $body = $this->visitDocument($document);

        // Close any remaining open sections
        $closeTags = '';
        while (!empty($this->sectionStack)) {
            array_pop($this->sectionStack);
            $closeTags .= "</section>\n";
        }

        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
             . "<article xmlns=\"http://docbook.org/ns/docbook\" version=\"5.0\">\n"
             . $body
             . $closeTags
             . "</article>\n";
    }

    public function getFormat(): string
    {
        return 'docbook';
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
        return $this->escapeXml($node->getText());
    }

    // ---------------------------------------------------------------
    // XML escaping
    // ---------------------------------------------------------------

    private function escapeXml(string $text): string
    {
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
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

    /**
     * Render children without escaping (for CDATA contexts)
     */
    public function renderChildrenRaw(ElementNode $node): string
    {
        $output = '';
        foreach ($node->getChildren() as $child) {
            if ($child instanceof TextNode) {
                $output .= $child->getText();
            } elseif ($child instanceof ElementNode) {
                $output .= $child->accept($this);
            }
        }
        return $output;
    }

    // ---------------------------------------------------------------
    // Inline formatting
    // ---------------------------------------------------------------

    protected function renderBold(ElementNode $node): string
    {
        return '<emphasis role="bold">' . $this->renderChildren($node) . '</emphasis>';
    }

    protected function renderItalic(ElementNode $node): string
    {
        return '<emphasis>' . $this->renderChildren($node) . '</emphasis>';
    }

    protected function renderStrong(ElementNode $node): string
    {
        return '<emphasis role="strong">' . $this->renderChildren($node) . '</emphasis>';
    }

    protected function renderEmphasis(ElementNode $node): string
    {
        return '<emphasis>' . $this->renderChildren($node) . '</emphasis>';
    }

    protected function renderUnderline(ElementNode $node): string
    {
        return '<emphasis role="underline">' . $this->renderChildren($node) . '</emphasis>';
    }

    protected function renderTt(ElementNode $node): string
    {
        return '<code>' . $this->renderChildren($node) . '</code>';
    }

    protected function renderSuperscript(ElementNode $node): string
    {
        return '<superscript>' . $this->renderChildren($node) . '</superscript>';
    }

    protected function renderSubscript(ElementNode $node): string
    {
        return '<subscript>' . $this->renderChildren($node) . '</subscript>';
    }

    protected function renderStrike(ElementNode $node): string
    {
        return '<emphasis role="strikethrough">' . $this->renderChildren($node) . '</emphasis>';
    }

    protected function renderDel(ElementNode $node): string
    {
        return '<emphasis role="deleted">' . $this->renderChildren($node) . '</emphasis>';
    }

    protected function renderIns(ElementNode $node): string
    {
        return '<emphasis role="inserted">' . $this->renderChildren($node) . '</emphasis>';
    }

    protected function renderBreak(ElementNode $node): string
    {
        return "<?linebreak?>";
    }

    // ---------------------------------------------------------------
    // Styling — DocBook uses phrase with role attributes
    // ---------------------------------------------------------------

    protected function renderColor(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $color = $this->escapeXml($attrs['color'] ?? '');

        return '<phrase role="color" condition="' . $color . '">'
             . $this->renderChildren($node) . '</phrase>';
    }

    protected function renderFont(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }

    protected function renderSize(ElementNode $node): string
    {
        return $this->renderChildren($node);
    }

    // ---------------------------------------------------------------
    // Alignment — limited support in DocBook
    // ---------------------------------------------------------------

    protected function renderCenter(ElementNode $node): string
    {
        return '<para role="center">' . $this->renderChildren($node) . '</para>';
    }

    protected function renderLeft(ElementNode $node): string
    {
        return '<para role="left">' . $this->renderChildren($node) . '</para>';
    }

    protected function renderRight(ElementNode $node): string
    {
        return '<para role="right">' . $this->renderChildren($node) . '</para>';
    }

    protected function renderJustify(ElementNode $node): string
    {
        return '<para>' . $this->renderChildren($node) . '</para>';
    }

    // ---------------------------------------------------------------
    // Block elements
    // ---------------------------------------------------------------

    protected function renderHeading(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $level = max(1, min(5, (int) ($attrs['level'] ?? 1)));

        // Close sections that are at the same or deeper level
        $closeTags = '';
        while (!empty($this->sectionStack) && end($this->sectionStack) >= $level) {
            array_pop($this->sectionStack);
            $closeTags .= "</section>\n";
        }

        $this->sectionStack[] = $level;

        return $closeTags
             . "<section>\n"
             . '<title>' . $this->renderChildren($node) . "</title>\n";
    }

    protected function renderHoriz(ElementNode $node): string
    {
        return '<phrase role="horiz"></phrase>';
    }

    protected function renderCode(ElementNode $node): string
    {
        $content = $this->renderChildrenRaw($node);
        $attrs = $node->getAttributes();
        $lang = $attrs['language'] ?? ($attrs['type'] ?? '');

        if ($lang !== '') {
            return '<programlisting language="' . $this->escapeXml($lang) . '"><![CDATA['
                 . $content . "]]></programlisting>\n";
        }

        return '<programlisting><![CDATA[' . $content . "]]></programlisting>\n";
    }

    protected function renderPreformatted(ElementNode $node): string
    {
        $content = $this->renderChildrenRaw($node);

        return '<literallayout><![CDATA[' . $content . "]]></literallayout>\n";
    }

    protected function renderRaw(ElementNode $node): string
    {
        return $this->renderChildrenRaw($node);
    }

    protected function renderBlockquote(ElementNode $node): string
    {
        return "<blockquote>\n" . $this->renderChildren($node) . "</blockquote>\n";
    }

    protected function renderParagraph(ElementNode $node): string
    {
        return '<para>' . $this->renderChildren($node) . "</para>\n";
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

        $hrefEscaped = $this->escapeXml($href);

        if ($text !== '' && $text !== $hrefEscaped) {
            return '<link xlink:href="' . $hrefEscaped . '"'
                 . ' xmlns:xlink="http://www.w3.org/1999/xlink">'
                 . $text . '</link>';
        }

        return '<link xlink:href="' . $hrefEscaped . '"'
             . ' xmlns:xlink="http://www.w3.org/1999/xlink">'
             . $hrefEscaped . '</link>';
    }

    protected function renderEmail(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $email = $attrs['email'] ?? '';
        $text = $this->renderChildren($node);

        $emailEscaped = $this->escapeXml($email);

        if ($text !== '') {
            return '<link xlink:href="mailto:' . $emailEscaped . '"'
                 . ' xmlns:xlink="http://www.w3.org/1999/xlink">'
                 . $text . '</link>';
        }

        return '<link xlink:href="mailto:' . $emailEscaped . '"'
             . ' xmlns:xlink="http://www.w3.org/1999/xlink">'
             . $emailEscaped . '</link>';
    }

    protected function renderWikilink(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $page = $attrs['page'] ?? '';
        $text = $this->renderChildren($node);

        if ($text !== '') {
            return $text;
        }

        return $this->escapeXml($page);
    }

    protected function renderPhplookup(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $function = $attrs['function'] ?? '';
        $text = $this->renderChildren($node);
        $href = 'https://www.php.net/' . $this->escapeXml($function);

        return '<link xlink:href="' . $href . '"'
             . ' xmlns:xlink="http://www.w3.org/1999/xlink">'
             . $text . '</link>';
    }

    // ---------------------------------------------------------------
    // Images
    // ---------------------------------------------------------------

    protected function renderImage(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $src = $this->escapeXml($attrs['src'] ?? '');
        $alt = $this->escapeXml($attrs['alt'] ?? '');

        return "<mediaobject>\n"
             . "<imageobject>\n"
             . '<imagedata fileref="' . $src . '" />' . "\n"
             . "</imageobject>\n"
             . ($alt !== '' ? "<caption><para>" . $alt . "</para></caption>\n" : '')
             . "</mediaobject>\n";
    }

    // ---------------------------------------------------------------
    // Lists
    // ---------------------------------------------------------------

    protected function renderList(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $type = $attrs['type'] ?? 'bullet';

        $isBullet = !in_array($type, ['1', 'number', 'a', 'A'], true);

        $content = $this->renderListChildren($node);

        if ($isBullet) {
            return "<itemizedlist>\n" . $content . "</itemizedlist>\n";
        }

        return "<orderedlist>\n" . $content . "</orderedlist>\n";
    }

    protected function renderListitem(ElementNode $node): string
    {
        $content = '';
        foreach ($node->getChildren() as $child) {
            $rendered = $child->accept($this);
            if ($child instanceof ElementNode && $child->getName() === 'list') {
                $content = rtrim($content) . "\n" . $rendered;
            } else {
                $content .= $rendered;
            }
        }

        return "<listitem><para>" . trim($content) . "</para></listitem>\n";
    }

    private function renderListChildren(ElementNode $node): string
    {
        $output = '';
        foreach ($node->getChildren() as $child) {
            $output .= $child->accept($this);
        }
        return $output;
    }

    // ---------------------------------------------------------------
    // Tables — DocBook informaltable with thead/tbody
    // ---------------------------------------------------------------

    protected function renderTable(ElementNode $node): string
    {
        $output = "<informaltable>\n";

        foreach ($node->getChildren() as $child) {
            if ($child instanceof ElementNode && $child->getName() === 'row') {
                $output .= $this->renderRow($child);
            }
        }

        $output .= "</informaltable>\n";

        return $output;
    }

    protected function renderRow(ElementNode $node): string
    {
        $output = "<tr>\n";
        foreach ($node->getChildren() as $child) {
            if ($child instanceof ElementNode && $child->getName() === 'cell') {
                $output .= $this->renderCell($child);
            }
        }
        $output .= "</tr>\n";

        return $output;
    }

    protected function renderCell(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $isHeader = (($attrs['type'] ?? 'data') === 'header');
        $tag = $isHeader ? 'th' : 'td';

        return '<' . $tag . '>' . $this->renderChildren($node) . '</' . $tag . ">\n";
    }

    // ---------------------------------------------------------------
    // Definition lists — DocBook glosslist
    // ---------------------------------------------------------------

    protected function renderDeflist(ElementNode $node): string
    {
        return "<glosslist>\n" . $this->renderDeflistChildren($node) . "</glosslist>\n";
    }

    private function renderDeflistChildren(ElementNode $node): string
    {
        $children = $node->getChildren();
        $output = '';
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

                $output .= "<glossentry>\n"
                         . '<glossterm>' . $term . "</glossterm>\n"
                         . '<glossdef><para>' . $def . "</para></glossdef>\n"
                         . "</glossentry>\n";
            }
        }

        return $output;
    }

    // ---------------------------------------------------------------
    // Revision marks
    // ---------------------------------------------------------------

    protected function renderReviseDel(ElementNode $node): string
    {
        return '<emphasis role="deleted">' . $this->renderChildren($node) . '</emphasis>';
    }

    protected function renderReviseIns(ElementNode $node): string
    {
        return '<emphasis role="inserted">' . $this->renderChildren($node) . '</emphasis>';
    }

    // ---------------------------------------------------------------
    // Suppressed / minimal elements
    // ---------------------------------------------------------------

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
        $attrs = $node->getAttributes();
        $name = $this->escapeXml($attrs['name'] ?? '');

        return '<anchor xml:id="' . $name . '" />';
    }
}
