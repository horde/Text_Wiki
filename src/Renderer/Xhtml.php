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
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class Xhtml implements Renderer, NodeVisitor
{
    /** @var array<string, callable(ElementNode, NodeVisitor): string> */
    private array $elementHandlers = [];

    /**
     * Register a custom element handler
     *
     * Handlers take priority over built-in render methods.
     * Signature: function(ElementNode $node, NodeVisitor $renderer): string
     *
     * @param string $tagName Tag name (case-insensitive)
     * @param callable(ElementNode, NodeVisitor): string $handler
     */
    public function setElementHandler(string $tagName, callable $handler): void
    {
        $this->elementHandlers[strtolower($tagName)] = $handler;
    }

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
        $key = strtolower($node->getName());

        if (isset($this->elementHandlers[$key])) {
            return ($this->elementHandlers[$key])($node, $this);
        }

        $tagName = $node->getName();
        $method = 'render' . ucfirst($tagName);

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
    public function renderChildren(ElementNode $node): string
    {
        $output = '';
        foreach ($node->getChildren() as $child) {
            $output .= $child->accept($this);
        }
        return $output;
    }

    /**
     * Render bold tag
     *
     * @param ElementNode $node Bold element
     *
     * @return string <strong>...</strong>
     */
    protected function renderBold(ElementNode $node): string
    {
        return '<strong>' . $this->renderChildren($node) . '</strong>';
    }

    /**
     * Render italic tag
     *
     * @param ElementNode $node Italic element
     *
     * @return string <em>...</em>
     */
    protected function renderItalic(ElementNode $node): string
    {
        return '<em>' . $this->renderChildren($node) . '</em>';
    }

    /**
     * Render underline tag
     *
     * @param ElementNode $node Underline element
     *
     * @return string <u>...</u>
     */
    protected function renderUnderline(ElementNode $node): string
    {
        return '<u>' . $this->renderChildren($node) . '</u>';
    }

    /**
     * Render URL link
     *
     * Supports both BBCode [url=...]...[/url] and Yawiki [url text] style.
     *
     * @param ElementNode $node URL element
     *
     * @return string <a href="...">...</a>
     */
    protected function renderUrl(ElementNode $node): string
    {
        $attrs = $node->getAttributes();

        // [url=http://...]text[/url] or Yawiki [url text]
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
     * Render image
     *
     * Supports both BBCode [img]url[/img] (URL from content) and
     * Yawiki [[image url]] (URL from 'src' attribute).
     *
     * @param ElementNode $node Image element
     *
     * @return string <img src="..." alt="..." />
     */
    protected function renderImage(ElementNode $node): string
    {
        $attrs = $node->getAttributes();

        // Yawiki style: src from attribute
        if (isset($attrs['src'])) {
            $src = htmlspecialchars($attrs['src'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $alt = htmlspecialchars($attrs['alt'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');

            $html = '<img src="' . $src . '" alt="' . $alt . '"';

            if (isset($attrs['link'])) {
                $html .= ' />';
                $link = htmlspecialchars($attrs['link'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                return '<a href="' . $link . '">' . $html . '</a>';
            }

            return $html . ' />';
        }

        // BBCode style: src from content
        $children = $node->getChildren();
        if (count($children) === 1 && $children[0] instanceof TextNode) {
            $src = htmlspecialchars($children[0]->getText(), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            $alt = isset($attrs['alt'])
                ? htmlspecialchars($attrs['alt'], ENT_QUOTES | ENT_HTML5, 'UTF-8')
                : '';

            return '<img src="' . $src . '" alt="' . $alt . '" />';
        }

        return '';
    }

    /**
     * Render blockquote
     *
     * Supports both BBCode [quote=Author]...[/quote] and
     * Yawiki > blockquote style.
     *
     * @param ElementNode $node Blockquote element
     *
     * @return string <blockquote>...</blockquote>
     */
    protected function renderBlockquote(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $output = '<blockquote>';

        // [quote=Author] - BBCode attribution
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

    protected function renderPreformatted(ElementNode $node): string
    {
        return '<pre>' . $this->renderChildren($node) . '</pre>';
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

        if (isset($attrs['type'])) {
            $type = $attrs['type'];

            // Numeric ordered list (BBCode '1' or wiki 'number')
            if ($type === '1' || $type === 'number') {
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
     * Render strikethrough tag
     *
     * @param ElementNode $node Strike element
     *
     * @return string <s>...</s>
     */
    protected function renderStrike(ElementNode $node): string
    {
        return '<s>' . $this->renderChildren($node) . '</s>';
    }

    /**
     * Render superscript tag
     *
     * @param ElementNode $node Superscript element
     *
     * @return string <sup>...</sup>
     */
    protected function renderSuperscript(ElementNode $node): string
    {
        return '<sup>' . $this->renderChildren($node) . '</sup>';
    }

    /**
     * Render subscript tag
     *
     * @param ElementNode $node Subscript element
     *
     * @return string <sub>...</sub>
     */
    protected function renderSubscript(ElementNode $node): string
    {
        return '<sub>' . $this->renderChildren($node) . '</sub>';
    }

    protected function renderDel(ElementNode $node): string
    {
        return '<del>' . $this->renderChildren($node) . '</del>';
    }

    protected function renderIns(ElementNode $node): string
    {
        return '<ins>' . $this->renderChildren($node) . '</ins>';
    }

    /**
     * Render horizontal rule tag
     *
     * @param ElementNode $node Horiz element
     *
     * @return string <hr />
     */
    protected function renderHoriz(ElementNode $node): string
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
     * Render [center]...[/center] alignment
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

    /**
     * Render strong emphasis
     *
     * @param ElementNode $node Strong element
     *
     * @return string <strong>...</strong>
     */
    protected function renderStrong(ElementNode $node): string
    {
        return '<strong>' . $this->renderChildren($node) . '</strong>';
    }

    /**
     * Render emphasis
     *
     * @param ElementNode $node Emphasis element
     *
     * @return string <em>...</em>
     */
    protected function renderEmphasis(ElementNode $node): string
    {
        return '<em>' . $this->renderChildren($node) . '</em>';
    }

    /**
     * Render monospace (teletype)
     *
     * @param ElementNode $node Tt element
     *
     * @return string <tt>...</tt>
     */
    protected function renderTt(ElementNode $node): string
    {
        return '<tt>' . $this->renderChildren($node) . '</tt>';
    }

    /**
     * Render heading with level
     *
     * @param ElementNode $node Heading element with 'level' attribute
     *
     * @return string <h1>...<h6>
     */
    protected function renderHeading(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $level = $attrs['level'] ?? 1;
        $level = max(1, min(6, (int)$level));
        $tag = 'h' . $level;

        return '<' . $tag . '>' . $this->renderChildren($node) . '</' . $tag . '>';
    }

    /**
     * Render line break
     *
     * @param ElementNode $node Break element
     *
     * @return string <br />
     */
    protected function renderBreak(ElementNode $node): string
    {
        return '<br />';
    }

    /**
     * Render ((freelink)) wiki link
     *
     * @param ElementNode $node Freelink element with 'page' attribute
     *
     * @return string <a href="...">...</a>
     */
    protected function renderFreelink(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $page = $attrs['page'] ?? '';
        $href = htmlspecialchars(str_replace(' ', '+', $page), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return '<a href="' . $href . '">' . $this->renderChildren($node) . '</a>';
    }

    /**
     * Render wiki page link (Cowiki wikilinks)
     *
     * @param ElementNode $node Wikilink element with 'page' and optional 'anchor' attributes
     *
     * @return string <a href="...">...</a>
     */
    protected function renderWikilink(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $page = $attrs['page'] ?? '';
        $anchor = $attrs['anchor'] ?? '';
        $href = htmlspecialchars(str_replace(' ', '+', $page), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($anchor !== '') {
            $href .= '#' . htmlspecialchars($anchor, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return '<a href="' . $href . '">' . $this->renderChildren($node) . '</a>';
    }

    /**
     * Render PHP manual lookup link
     *
     * @param ElementNode $node Phplookup element with 'function' attribute
     *
     * @return string <a href="php.net/...">...</a>
     */
    protected function renderPhplookup(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $function = $attrs['function'] ?? '';
        $href = 'https://www.php.net/' . htmlspecialchars($function, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return '<a href="' . $href . '">' . $this->renderChildren($node) . '</a>';
    }

    /**
     * Render table
     *
     * @param ElementNode $node Table element
     *
     * @return string <table>...</table>
     */
    protected function renderTable(ElementNode $node): string
    {
        return '<table>' . $this->renderChildren($node) . '</table>';
    }

    /**
     * Render table row
     *
     * @param ElementNode $node Row element
     *
     * @return string <tr>...</tr>
     */
    protected function renderRow(ElementNode $node): string
    {
        return '<tr>' . $this->renderChildren($node) . '</tr>';
    }

    /**
     * Render table cell
     *
     * @param ElementNode $node Cell element with optional 'type' attribute
     *
     * @return string <td>...</td> or <th>...</th>
     */
    protected function renderCell(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $tag = ($attrs['type'] ?? 'data') === 'header' ? 'th' : 'td';

        return '<' . $tag . '>' . $this->renderChildren($node) . '</' . $tag . '>';
    }

    /**
     * Render definition list
     *
     * @param ElementNode $node Deflist element
     *
     * @return string <dl>...</dl>
     */
    protected function renderDeflist(ElementNode $node): string
    {
        return '<dl>' . $this->renderChildren($node) . '</dl>';
    }

    /**
     * Render definition term
     *
     * @param ElementNode $node Defterm element
     *
     * @return string <dt>...</dt>
     */
    protected function renderDefterm(ElementNode $node): string
    {
        return '<dt>' . $this->renderChildren($node) . '</dt>';
    }

    /**
     * Render definition description
     *
     * @param ElementNode $node Defdef element
     *
     * @return string <dd>...</dd>
     */
    protected function renderDefdef(ElementNode $node): string
    {
        return '<dd>' . $this->renderChildren($node) . '</dd>';
    }

    /**
     * Render colored text
     *
     * @param ElementNode $node Colortext element with 'color' attribute
     *
     * @return string <span style="color: ...">...</span>
     */
    protected function renderColortext(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        if (!isset($attrs['color'])) {
            return $this->renderChildren($node);
        }

        $color = htmlspecialchars($attrs['color'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return '<span style="color: ' . $color . ';">' . $this->renderChildren($node) . '</span>';
    }

    /**
     * Render anchor/ID target
     *
     * @param ElementNode $node Anchor element with 'name' attribute
     *
     * @return string <a id="..."></a>
     */
    protected function renderAnchor(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $name = htmlspecialchars($attrs['name'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return '<a id="' . $name . '"></a>';
    }

    /**
     * Render table of contents
     *
     * Placeholder: actual TOC generation requires a second pass.
     *
     * @param ElementNode $node Toc element
     *
     * @return string TOC placeholder div
     */
    protected function renderToc(ElementNode $node): string
    {
        return '<div class="toc"></div>';
    }

    /**
     * Render paragraph
     *
     * @param ElementNode $node Paragraph element
     *
     * @return string <p>...</p>
     */
    protected function renderParagraph(ElementNode $node): string
    {
        return '<p>' . $this->renderChildren($node) . '</p>';
    }

    /**
     * Render deleted revision markup
     *
     * @param ElementNode $node Revise_del element
     *
     * @return string <del>...</del>
     */
    protected function renderRevise_del(ElementNode $node): string
    {
        return '<del>' . $this->renderChildren($node) . '</del>';
    }

    /**
     * Render inserted revision markup
     *
     * @param ElementNode $node Revise_ins element
     *
     * @return string <ins>...</ins>
     */
    protected function renderRevise_ins(ElementNode $node): string
    {
        return '<ins>' . $this->renderChildren($node) . '</ins>';
    }
}
