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

    /** @var list<array{level: int, text: string, id: string}> */
    private array $headings = [];

    private int $headingCounter = 0;

    private int $headingRenderIndex = 0;

    private bool $headingIds = false;

    private bool $hasToc = false;

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

    public function enableHeadingIds(bool $enable = true): void
    {
        $this->headingIds = $enable;
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
        $this->headings = [];
        $this->headingCounter = 0;
        $this->headingRenderIndex = 0;
        $this->hasToc = false;
        $this->collectHeadings($document);

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
        return htmlspecialchars($node->getText(), ENT_COMPAT | ENT_HTML5, 'UTF-8');
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

    private function collectHeadings(Node $node): void
    {
        foreach ($node->getChildren() as $child) {
            if ($child instanceof ElementNode) {
                if ($child->getName() === 'toc') {
                    $this->hasToc = true;
                } elseif ($child->getName() === 'heading') {
                    $attrs = $child->getAttributes();
                    $level = (int) ($attrs['level'] ?? 1);
                    $text = $this->extractPlainText($child);
                    $slug = $this->slugify($text);
                    $id = 'toc-' . $this->headingCounter++ . '-' . $slug;
                    $this->headings[] = ['level' => $level, 'text' => $text, 'id' => $id];
                }
            }
            $this->collectHeadings($child);
        }
    }

    private function extractPlainText(Node $node): string
    {
        $text = '';
        foreach ($node->getChildren() as $child) {
            if ($child instanceof TextNode) {
                $text .= $child->getText();
            } else {
                $text .= $this->extractPlainText($child);
            }
        }

        return $text;
    }

    private function slugify(string $text): string
    {
        $slug = strtolower(trim($text));
        $slug = (string) preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        $slug = substr($slug, 0, 60);

        return $slug !== '' ? $slug : 'heading';
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

        // [url=http://...]text[/url] or Yawiki [url text] or CommonMark [text](url "title")
        if (isset($attrs['href'])) {
            $href = $this->sanitizeUrl($attrs['href']);
            $html = '<a href="' . $href . '"';
            if (isset($attrs['title']) && $attrs['title'] !== '') {
                $html .= ' title="' . htmlspecialchars($attrs['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"';
            }
            $html .= '>' . $this->renderChildren($node) . '</a>';
            return $html;
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

        // Yawiki/CommonMark style: src from attribute
        if (isset($attrs['src'])) {
            $src = $this->sanitizeUrl($attrs['src']);
            $alt = htmlspecialchars($attrs['alt'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');

            $html = '<img src="' . $src . '" alt="' . $alt . '"';

            if (isset($attrs['title']) && $attrs['title'] !== '') {
                $html .= ' title="' . htmlspecialchars($attrs['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"';
            }

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
        $output = "<blockquote>\n";

        // [quote=Author] - BBCode attribution
        if (isset($attrs['author'])) {
            $author = htmlspecialchars($attrs['author'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $output .= '<p><strong>' . $author . " wrote:</strong></p>\n";
        }

        $output .= $this->renderChildren($node);
        $output .= "</blockquote>\n";

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

        // Check for language attribute — reject if it contains HTML-unsafe characters
        if (isset($attrs['language']) && $attrs['language'] !== ''
            && !preg_match('/[<>&"\']/', $attrs['language'])) {
            $language = $attrs['language'];
            $languageClass = htmlspecialchars($language, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return '<pre><code class="language-' . $languageClass . '">'
                 . $this->renderChildren($node)
                 . "</code></pre>\n";
        }

        // Default: no language class
        return '<pre><code>' . $this->renderChildren($node) . "</code></pre>\n";
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

            // Ordered list (BBCode '1', 'number', or CommonMark 'ordered')
            if ($type === '1' || $type === 'number' || $type === 'ordered') {
                $start = isset($attrs['start']) ? (int) $attrs['start'] : 1;
                if ($start !== 1) {
                    return '<ol start="' . $start . "\">\n" . $this->renderChildren($node) . "</ol>\n";
                }
                return "<ol>\n" . $this->renderChildren($node) . "</ol>\n";
            }

            // Alphabetic ordered lists
            if ($type === 'a' || $type === 'A') {
                $typeAttr = htmlspecialchars($type, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                return '<ol type="' . $typeAttr . "\">\n" . $this->renderChildren($node) . "</ol>\n";
            }

            // Bullet list (CommonMark 'bullet')
            if ($type === 'bullet') {
                return "<ul>\n" . $this->renderChildren($node) . "</ul>\n";
            }
        }

        // Default: unordered list (simple, no extra newlines)
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
        $tight = $node->getAttributes()['tight'] ?? false;

        if ($tight) {
            // In tight lists, render paragraphs without <p> wrappers
            // but keep other block elements (code, blockquote, sub-lists) as-is
            $children = $node->getChildren();
            $hasBlockElements = false;
            foreach ($children as $child) {
                if ($child instanceof ElementNode && $child->getName() !== 'paragraph') {
                    $hasBlockElements = true;
                    break;
                }
            }

            if ($hasBlockElements) {
                // Mix of paragraph + block elements (e.g., text + sub-list)
                // Render paragraphs inline (no <p>), block elements on their own lines
                $parts = [];
                $firstIsParagraph = false;
                foreach ($children as $idx => $child) {
                    if ($child instanceof ElementNode && $child->getName() === 'paragraph') {
                        if ($idx === 0) {
                            $firstIsParagraph = true;
                        }
                        $rendered = $child->accept($this);
                        $rendered = preg_replace('/<p>(.*?)<\/p>\n?/s', '$1', $rendered) ?? $rendered;
                        $parts[] = $rendered;
                    } else {
                        $parts[] = $child->accept($this);
                    }
                }
                // Join parts, avoiding double newlines
                $content = '';
                foreach ($parts as $i => $part) {
                    if ($i > 0 && !str_ends_with($content, "\n")) {
                        $content .= "\n";
                    }
                    $content .= $part;
                }
                $content = rtrim($content, "\n");
                $lastChild = end($children);
                $lastIsParagraph = $lastChild instanceof ElementNode && $lastChild->getName() === 'paragraph';
                if ($firstIsParagraph) {
                    return '<li>' . $content . "\n</li>\n";
                }
                if ($lastIsParagraph) {
                    return "<li>\n" . $content . "</li>\n";
                }
                return "<li>\n" . $content . "\n</li>\n";
            }

            // Only paragraphs — strip <p> wrappers, inline content
            $content = $this->renderChildren($node);
            $content = preg_replace('/<p>(.*?)<\/p>/s', '$1', $content) ?? $content;
            $content = rtrim($content, "\n");
            return '<li>' . $content . "</li>\n";
        }

        // Check if this has paragraph children (CommonMark loose list)
        $hasParagraphs = false;
        foreach ($node->getChildren() as $child) {
            if ($child instanceof ElementNode && $child->getName() === 'paragraph') {
                $hasParagraphs = true;
                break;
            }
        }

        $content = $this->renderChildren($node);
        if ($content === '') {
            return "<li></li>\n";
        }

        // Loose list with paragraphs: newline after <li>
        if ($hasParagraphs) {
            return "<li>\n" . $content . "</li>\n";
        }

        // Simple list item (non-CommonMark): compact rendering
        return '<li>' . rtrim($content, "\n") . "</li>\n";
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
        return '<span style="color: ' . $color . ';">' . $this->renderChildren($node) . '</span>';
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
        return '<del>' . $this->renderChildren($node) . '</del>';
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
        return "<hr />\n";
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
     * Render inline code / monospace text
     *
     * @param ElementNode $node Tt element
     *
     * @return string <code>...</code>
     */
    protected function renderTt(ElementNode $node): string
    {
        return '<code>' . $this->renderChildren($node) . '</code>';
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
        $level = max(1, min(6, (int) $level));
        $tag = 'h' . $level;

        $idAttr = '';
        if (($this->headingIds || $this->hasToc)
            && isset($this->headings[$this->headingRenderIndex])
        ) {
            $id = htmlspecialchars(
                $this->headings[$this->headingRenderIndex]['id'],
                ENT_QUOTES | ENT_HTML5,
                'UTF-8',
            );
            $idAttr = ' id="' . $id . '"';
        }
        $this->headingRenderIndex++;

        return '<' . $tag . $idAttr . '>' . $this->renderChildren($node) . '</' . $tag . ">\n";
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
        return "<br />\n";
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
        $children = $node->getChildren();
        $headerRows = '';
        $bodyRows = '';
        foreach ($children as $child) {
            $rendered = $child->accept($this);
            $isHeader = ($child instanceof ElementNode)
                && ($child->getAttributes()['header'] ?? false) === true;
            if ($isHeader) {
                $headerRows .= $rendered;
            } else {
                $bodyRows .= $rendered;
            }
        }

        $result = "<table>\n";
        if ($headerRows !== '') {
            $result .= "<thead>\n" . $headerRows . "</thead>\n";
        }
        if ($bodyRows !== '') {
            $result .= "<tbody>\n" . $bodyRows . "</tbody>\n";
        }
        $result .= "</table>\n";
        return $result;
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
        return "<tr>\n" . $this->renderChildren($node) . "</tr>\n";
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

        // CommonMark header boolean or legacy 'type' string
        $isHeader = ($attrs['header'] ?? false) === true
            || ($attrs['type'] ?? 'data') === 'header';
        $tag = $isHeader ? 'th' : 'td';

        $align = $attrs['align'] ?? '';
        if ($align !== '') {
            $align = htmlspecialchars($align, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return '<' . $tag . ' align="' . $align . '">' . $this->renderChildren($node) . '</' . $tag . ">\n";
        }

        return '<' . $tag . '>' . $this->renderChildren($node) . '</' . $tag . ">\n";
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
     * @param ElementNode $node Toc element with optional 'depth' attribute
     *
     * @return string <nav id="toc">...</nav> or empty string if no headings
     */
    protected function renderToc(ElementNode $node): string
    {
        if ($this->headings === []) {
            return '';
        }

        $attrs = $node->getAttributes();
        $maxDepth = isset($attrs['depth']) ? (int) $attrs['depth'] : 6;

        $minLevel = PHP_INT_MAX;
        foreach ($this->headings as $h) {
            if ($h['level'] < $minLevel) {
                $minLevel = $h['level'];
            }
        }

        $html = '<nav id="toc">' . "\n";
        $html .= '<h2>Table of Contents</h2>' . "\n";

        $currentDepth = 0;
        $itemCount = 0;

        foreach ($this->headings as $heading) {
            $relativeLevel = $heading['level'] - $minLevel + 1;
            if ($relativeLevel > $maxDepth) {
                continue;
            }

            if ($relativeLevel > $currentDepth) {
                while ($currentDepth < $relativeLevel) {
                    $html .= '<ol>' . "\n";
                    $currentDepth++;
                }
            } elseif ($relativeLevel < $currentDepth) {
                while ($currentDepth > $relativeLevel) {
                    $html .= '</li>' . "\n" . '</ol>' . "\n";
                    $currentDepth--;
                }
                $html .= '</li>' . "\n";
            } else {
                if ($itemCount > 0) {
                    $html .= '</li>' . "\n";
                }
            }

            $id = htmlspecialchars($heading['id'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = htmlspecialchars($heading['text'], ENT_COMPAT | ENT_HTML5, 'UTF-8');
            $html .= '<li><a href="#' . $id . '">' . $text . '</a>' . "\n";
            $itemCount++;
        }

        while ($currentDepth > 0) {
            $html .= '</li>' . "\n" . '</ol>' . "\n";
            $currentDepth--;
        }

        $html .= '</nav>' . "\n";

        return $html;
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
        return '<p>' . $this->renderChildren($node) . "</p>\n";
    }

    /**
     * Render soft line break (CommonMark)
     *
     * @param ElementNode $node Softbreak element
     *
     * @return string newline character
     */
    protected function renderSoftbreak(ElementNode $node): string
    {
        return "\n";
    }

    /**
     * Render raw HTML block (CommonMark)
     *
     * @param ElementNode $node Htmlblock element
     *
     * @return string Raw HTML content
     */
    protected function renderHtmlblock(ElementNode $node): string
    {
        return $this->renderChildrenRaw($node) . "\n";
    }

    /**
     * Render raw inline HTML (CommonMark)
     *
     * @param ElementNode $node Htmlinline element
     *
     * @return string Raw HTML content
     */
    protected function renderHtmlinline(ElementNode $node): string
    {
        return $this->renderChildrenRaw($node);
    }

    /**
     * Render children without HTML escaping (for raw HTML nodes)
     *
     * @param ElementNode $node Parent node
     *
     * @return string Unescaped children text
     */
    public function renderChildrenRaw(ElementNode $node): string
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

    /**
     * Filter GFM disallowed raw HTML tags by replacing < with &lt;
     */
    private function filterDisallowedHtml(string $html): string
    {
        return preg_replace(
            '/<(\/?(?:title|textarea|style|xmp|iframe|noembed|noframes|script|plaintext)(?:\s|>|\/?>))/i',
            '&lt;$1',
            $html
        ) ?? $html;
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

    /**
     * Sanitize URL for use in href/src attributes
     *
     * Percent-encodes unsafe characters per CommonMark spec while
     * preserving existing percent-encoding and HTML entity-escaping
     * the result for safe attribute inclusion.
     */
    private function sanitizeUrl(string $url): string
    {
        // Percent-encode characters that should not appear raw in URLs
        // but preserve existing percent-encoding (%XX sequences)
        $url = preg_replace_callback(
            '/[^a-zA-Z0-9._~:\/\?#@!\$&\'\(\)\*\+,;=\-%]/',
            fn(array $m) => rawurlencode($m[0]),
            $url
        ) ?? $url;

        return htmlspecialchars($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
