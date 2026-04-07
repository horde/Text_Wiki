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
use Horde\Text\Wiki\Node\TextNode;
use Horde\Text\Wiki\NodeVisitor;
use Horde\Text\Wiki\Renderer;

/**
 * XHTML renderer for typed AST (DocumentNode/ElementNode/TextNode)
 *
 * Renders the typed document tree to XHTML using visitor pattern.
 * Works with AST from modern parsers (BBCode, future Mediawiki, etc.).
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class Xhtml implements Renderer, NodeVisitor
{
    /**
     * Render document tree to XHTML
     *
     * @param DocumentNode $document Document root
     *
     * @return string XHTML output
     */
    public function render(DocumentNode $document): string
    {
        return $document->accept($this);
    }

    /**
     * Get output format
     *
     * @return string 'xhtml'
     */
    public function getFormat(): string
    {
        return 'xhtml';
    }

    /**
     * Visit document root
     *
     * Renders all children and concatenates output.
     *
     * @param DocumentNode $node Document root
     *
     * @return string Rendered children
     */
    public function visitDocument(DocumentNode $node): string
    {
        $output = '';
        foreach ($node->getChildren() as $child) {
            $output .= $child->accept($this);
        }
        return $output;
    }

    /**
     * Visit element node (tag)
     *
     * Dispatches to specific tag rendering methods.
     *
     * @param ElementNode $node Element node
     *
     * @return string Rendered tag
     */
    public function visitElement(ElementNode $node): string
    {
        $tagName = $node->getName();

        // Special case: map '*' to method-safe name
        $methodName = ($tagName === '*') ? 'listitem' : $tagName;
        $method = 'render' . ucfirst($methodName);

        // If specific render method exists, use it
        if (method_exists($this, $method)) {
            return $this->$method($node);
        }

        // Fallback: render children only (treat as transparent)
        return $this->renderChildren($node);
    }

    /**
     * Visit text node
     *
     * Escapes text content for XHTML output.
     *
     * @param TextNode $node Text node
     *
     * @return string Escaped text
     */
    public function visitText(TextNode $node): string
    {
        return htmlspecialchars($node->getText(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Render children of a node
     *
     * Helper method to render all child nodes.
     *
     * @param ElementNode $node Parent node
     *
     * @return string Rendered children
     */
    protected function renderChildren(ElementNode $node): string
    {
        $output = '';
        foreach ($node->getChildren() as $child) {
            $output .= $child->accept($this);
        }
        return $output;
    }

    /**
     * Render [b]bold[/b] tag
     *
     * @param ElementNode $node Bold element
     *
     * @return string <strong>...</strong>
     */
    protected function renderB(ElementNode $node): string
    {
        return '<strong>' . $this->renderChildren($node) . '</strong>';
    }

    /**
     * Render [i]italic[/i] tag
     *
     * @param ElementNode $node Italic element
     *
     * @return string <em>...</em>
     */
    protected function renderI(ElementNode $node): string
    {
        return '<em>' . $this->renderChildren($node) . '</em>';
    }

    /**
     * Render [u]underline[/u] tag
     *
     * @param ElementNode $node Underline element
     *
     * @return string <u>...</u>
     */
    protected function renderU(ElementNode $node): string
    {
        return '<u>' . $this->renderChildren($node) . '</u>';
    }

    /**
     * Render [url]...[/url] or [url=...]...[/url] tag
     *
     * @param ElementNode $node URL element
     *
     * @return string <a href="...">...</a>
     */
    protected function renderUrl(ElementNode $node): string
    {
        $attrs = $node->getAttributes();

        // [url=http://...]text[/url]
        if (isset($attrs['href'])) {
            $href = htmlspecialchars($attrs['href'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return '<a href="' . $href . '">' . $this->renderChildren($node) . '</a>';
        }

        // [url]http://...[/url] - href comes from text content
        $children = $node->getChildren();
        if (count($children) === 1 && $children[0] instanceof TextNode) {
            $href = htmlspecialchars($children[0]->getText(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return '<a href="' . $href . '">' . $href . '</a>';
        }

        // Malformed - just render children
        return $this->renderChildren($node);
    }

    /**
     * Render [img]http://...[/img] tag
     *
     * @param ElementNode $node Image element
     *
     * @return string <img src="..." alt="" />
     */
    protected function renderImg(ElementNode $node): string
    {
        $children = $node->getChildren();
        if (count($children) === 1 && $children[0] instanceof TextNode) {
            $src = htmlspecialchars($children[0]->getText(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return '<img src="' . $src . '" alt="" />';
        }

        // Malformed - render nothing
        return '';
    }

    /**
     * Render [quote]...[/quote] or [quote=Author]...[/quote] tag
     *
     * @param ElementNode $node Quote element
     *
     * @return string <blockquote>...</blockquote>
     */
    protected function renderQuote(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $output = '<blockquote>';

        // [quote=Author]
        if (isset($attrs['author'])) {
            $author = htmlspecialchars($attrs['author'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $output .= '<p><strong>' . $author . ' wrote:</strong></p>';
        }

        $output .= $this->renderChildren($node);
        $output .= '</blockquote>';

        return $output;
    }

    /**
     * Render [code]...[/code] tag
     *
     * @param ElementNode $node Code element
     *
     * @return string <pre><code>...</code></pre>
     */
    protected function renderCode(ElementNode $node): string
    {
        return '<pre><code>' . $this->renderChildren($node) . '</code></pre>';
    }

    /**
     * Render [list]...[/list] tag
     *
     * @param ElementNode $node List element
     *
     * @return string <ul>...</ul>
     */
    protected function renderList(ElementNode $node): string
    {
        return '<ul>' . $this->renderChildren($node) . '</ul>';
    }

    /**
     * Render [*] list item tag
     *
     * @param ElementNode $node List item element
     *
     * @return string <li>...</li>
     */
    protected function renderListitem(ElementNode $node): string
    {
        return '<li>' . $this->renderChildren($node) . '</li>';
    }

    /**
     * Render [color=#RGB]...[/color] tag
     *
     * @param ElementNode $node Color element
     *
     * @return string <span style="color: ...">...</span>
     */
    protected function renderColor(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        if (!isset($attrs['color'])) {
            return $this->renderChildren($node);
        }

        $color = htmlspecialchars($attrs['color'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return '<span style="color: ' . $color . '">' . $this->renderChildren($node) . '</span>';
    }

    /**
     * Render [font=Arial]...[/font] tag
     *
     * @param ElementNode $node Font element
     *
     * @return string <span style="font-family: ...">...</span>
     */
    protected function renderFont(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        if (!isset($attrs['font'])) {
            return $this->renderChildren($node);
        }

        $font = htmlspecialchars($attrs['font'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return '<span style="font-family: ' . $font . '">' . $this->renderChildren($node) . '</span>';
    }

    /**
     * Render [size=14]...[/size] tag
     *
     * @param ElementNode $node Size element
     *
     * @return string <span style="font-size: ...pt">...</span>
     */
    protected function renderSize(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        if (!isset($attrs['size'])) {
            return $this->renderChildren($node);
        }

        $size = (int) $attrs['size'];
        return '<span style="font-size: ' . $size . 'pt">' . $this->renderChildren($node) . '</span>';
    }
}
