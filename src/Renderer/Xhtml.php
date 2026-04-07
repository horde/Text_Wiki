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
     * Render [img]http://...[/img] or [img alt="..."]http://...[/img] tag
     *
     * @param ElementNode $node Image element
     *
     * @return string <img src="..." alt="..." />
     */
    protected function renderImg(ElementNode $node): string
    {
        $children = $node->getChildren();
        if (count($children) === 1 && $children[0] instanceof TextNode) {
            $src = htmlspecialchars($children[0]->getText(), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // Get alt text from attributes (default to empty string)
            $attrs = $node->getAttributes();
            $alt = isset($attrs['alt'])
                ? htmlspecialchars($attrs['alt'], ENT_QUOTES | ENT_HTML5, 'UTF-8')
                : '';

            return '<img src="' . $src . '" alt="' . $alt . '" />';
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
     * Render [code]...[/code] or [code=language]...[/code] tag
     *
     * Supports language attribute for syntax highlighters:
     * - [code] → <pre><code>...</code></pre>
     * - [code=php] → <pre><code class="language-php">...</code></pre>
     *
     * @param ElementNode $node Code element
     *
     * @return string <pre><code>...</code></pre>
     */
    protected function renderCode(ElementNode $node): string
    {
        $attrs = $node->getAttributes();

        // Check for language attribute
        if (isset($attrs['language']) && $attrs['language'] !== '') {
            $language = $attrs['language'];

            // Sanitize language (alphanumeric, hyphen, underscore only)
            if (preg_match('/^[a-zA-Z0-9_-]+$/', $language)) {
                $languageClass = htmlspecialchars($language, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                return '<pre><code class="language-' . $languageClass . '">'
                     . $this->renderChildren($node)
                     . '</code></pre>';
            }
        }

        // Default: no language class
        return '<pre><code>' . $this->renderChildren($node) . '</code></pre>';
    }

    /**
     * Render [list]...[/list] tag
     *
     * Supports:
     * - [list] → <ul> (unordered)
     * - [list=1] → <ol> (ordered, numeric)
     * - [list=a] → <ol type="a"> (ordered, lowercase alpha)
     * - [list=A] → <ol type="A"> (ordered, uppercase alpha)
     *
     * @param ElementNode $node List element
     *
     * @return string <ul>...</ul> or <ol>...</ol>
     */
    protected function renderList(ElementNode $node): string
    {
        $attrs = $node->getAttributes();

        // Check for type attribute
        if (isset($attrs['type'])) {
            $type = $attrs['type'];

            // Numeric ordered list
            if ($type === '1') {
                return '<ol>' . $this->renderChildren($node) . '</ol>';
            }

            // Alphabetic ordered lists
            if ($type === 'a' || $type === 'A') {
                $typeAttr = htmlspecialchars($type, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                return '<ol type="' . $typeAttr . '">' . $this->renderChildren($node) . '</ol>';
            }
        }

        // Default: unordered list
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

    /**
     * Render [s]strikethrough[/s] tag
     *
     * @param ElementNode $node Strike element
     *
     * @return string <s>...</s>
     */
    protected function renderS(ElementNode $node): string
    {
        return '<s>' . $this->renderChildren($node) . '</s>';
    }

    /**
     * Render [sup]superscript[/sup] tag
     *
     * @param ElementNode $node Superscript element
     *
     * @return string <sup>...</sup>
     */
    protected function renderSup(ElementNode $node): string
    {
        return '<sup>' . $this->renderChildren($node) . '</sup>';
    }

    /**
     * Render [sub]subscript[/sub] tag
     *
     * @param ElementNode $node Subscript element
     *
     * @return string <sub>...</sub>
     */
    protected function renderSub(ElementNode $node): string
    {
        return '<sub>' . $this->renderChildren($node) . '</sub>';
    }

    /**
     * Render [hr] horizontal rule tag
     *
     * @param ElementNode $node HR element
     *
     * @return string <hr />
     */
    protected function renderHr(ElementNode $node): string
    {
        return '<hr />';
    }

    /**
     * Render [email]...[/email] or [email=...]...[/email] tag
     *
     * @param ElementNode $node Email element
     *
     * @return string <a href="mailto:...">...</a>
     */
    protected function renderEmail(ElementNode $node): string
    {
        $attrs = $node->getAttributes();

        // [email=address@example.com]text[/email]
        if (isset($attrs['email'])) {
            $email = htmlspecialchars($attrs['email'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // Validate email
            if (filter_var($attrs['email'], FILTER_VALIDATE_EMAIL) === false) {
                return $this->renderChildren($node);
            }

            return '<a href="mailto:' . $email . '">' . $this->renderChildren($node) . '</a>';
        }

        // [email]address@example.com[/email] - email comes from text content
        $children = $node->getChildren();
        if (count($children) === 1 && $children[0] instanceof TextNode) {
            $email = $children[0]->getText();

            // Validate email
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                return htmlspecialchars($email, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            $emailEscaped = htmlspecialchars($email, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return '<a href="mailto:' . $emailEscaped . '">' . $emailEscaped . '</a>';
        }

        // Malformed - just render children
        return $this->renderChildren($node);
    }

    /**
     * Render [youtube]VIDEO_ID[/youtube] tag
     *
     * @param ElementNode $node YouTube element
     *
     * @return string <iframe>...</iframe> or empty string if invalid
     */
    protected function renderYoutube(ElementNode $node): string
    {
        $children = $node->getChildren();
        if (count($children) === 1 && $children[0] instanceof TextNode) {
            $videoId = trim($children[0]->getText());

            // Validate YouTube video ID format (11 chars: alphanumeric, underscore, hyphen)
            if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $videoId) !== 1) {
                return '';
            }

            $videoId = htmlspecialchars($videoId, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return '<iframe width="560" height="315" '
                 . 'src="https://www.youtube.com/embed/' . $videoId . '" '
                 . 'frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" '
                 . 'allowfullscreen></iframe>';
        }

        // Malformed - render nothing
        return '';
    }

    /**
     * Render [center]...[/center] tag
     *
     * Traditional XHTML with align attribute.
     *
     * @param ElementNode $node Center element
     *
     * @return string <div align="center">...</div>
     */
    protected function renderCenter(ElementNode $node): string
    {
        return '<div align="center">' . $this->renderChildren($node) . '</div>';
    }

    /**
     * Render [left]...[/left] tag
     *
     * Traditional XHTML with align attribute.
     *
     * @param ElementNode $node Left element
     *
     * @return string <div align="left">...</div>
     */
    protected function renderLeft(ElementNode $node): string
    {
        return '<div align="left">' . $this->renderChildren($node) . '</div>';
    }

    /**
     * Render [right]...[/right] tag
     *
     * Traditional XHTML with align attribute.
     *
     * @param ElementNode $node Right element
     *
     * @return string <div align="right">...</div>
     */
    protected function renderRight(ElementNode $node): string
    {
        return '<div align="right">' . $this->renderChildren($node) . '</div>';
    }

    /**
     * Render [justify]...[/justify] tag
     *
     * Traditional XHTML with align attribute.
     *
     * @param ElementNode $node Justify element
     *
     * @return string <div align="justify">...</div>
     */
    protected function renderJustify(ElementNode $node): string
    {
        return '<div align="justify">' . $this->renderChildren($node) . '</div>';
    }
}
