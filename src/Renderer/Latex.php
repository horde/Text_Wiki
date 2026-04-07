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
 * LaTeX renderer for typed AST
 *
 * Renders the typed document tree to LaTeX markup. Produces a complete
 * document with \documentclass, \begin{document}, \end{document} wrapper.
 *
 * LaTeX special characters are escaped in text nodes. Block elements
 * are separated by newlines. Code/preformatted blocks use verbatim
 * environments. Lists use itemize/enumerate. Tables use tabular.
 *
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class Latex implements Renderer, NodeVisitor
{
    private const BLOCK_ELEMENTS = [
        'heading', 'horiz', 'code', 'raw', 'blockquote',
        'paragraph', 'table', 'list', 'deflist', 'center',
        'left', 'right', 'justify', 'preformatted',
    ];

    private int $listDepth = 0;

    /** @var array<string> Stack of 'bullet'|'number' per nesting level */
    private array $listTypeStack = [];

    public function render(DocumentNode $document): string
    {
        $this->listDepth = 0;
        $this->listTypeStack = [];

        $body = $this->visitDocument($document);

        return "\\documentclass{article}\n"
             . "\\usepackage{ulem}\n"
             . "\\usepackage{graphicx}\n"
             . "\\usepackage{hyperref}\n"
             . "\\pagestyle{headings}\n"
             . "\\begin{document}\n"
             . $body
             . "\\end{document}\n";
    }

    public function getFormat(): string
    {
        return 'latex';
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
        return $this->escapeLatex($node->getText());
    }

    // ---------------------------------------------------------------
    // LaTeX escaping
    // ---------------------------------------------------------------

    private function escapeLatex(string $text): string
    {
        // Order matters: backslash first, then others
        $text = str_replace('\\', '\\textbackslash{}', $text);
        $text = str_replace('#', '\\#', $text);
        $text = str_replace('$', '\\$', $text);
        $text = str_replace('%', '\\%', $text);
        $text = str_replace('&', '\\&', $text);
        $text = str_replace('_', '\\_', $text);
        $text = str_replace('{', '\\{', $text);
        $text = str_replace('}', '\\}', $text);
        $text = str_replace('^', '\\^{}', $text);
        $text = str_replace('~', '\\textasciitilde{}', $text);

        return $text;
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

    protected function renderChildren(ElementNode $node): string
    {
        return $this->renderNodeList($node->getChildren());
    }

    /**
     * Render children without escaping (for verbatim contexts)
     */
    protected function renderChildrenRaw(ElementNode $node): string
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
        return '\\textbf{' . $this->renderChildren($node) . '}';
    }

    protected function renderItalic(ElementNode $node): string
    {
        return '\\textit{' . $this->renderChildren($node) . '}';
    }

    protected function renderStrong(ElementNode $node): string
    {
        return '\\textbf{' . $this->renderChildren($node) . '}';
    }

    protected function renderEmphasis(ElementNode $node): string
    {
        return '\\textsl{' . $this->renderChildren($node) . '}';
    }

    protected function renderUnderline(ElementNode $node): string
    {
        return '\\underline{' . $this->renderChildren($node) . '}';
    }

    protected function renderTt(ElementNode $node): string
    {
        return '\\texttt{' . $this->renderChildren($node) . '}';
    }

    protected function renderSuperscript(ElementNode $node): string
    {
        return '\\textsuperscript{' . $this->renderChildren($node) . '}';
    }

    protected function renderSubscript(ElementNode $node): string
    {
        return '\\textsubscript{' . $this->renderChildren($node) . '}';
    }

    protected function renderStrike(ElementNode $node): string
    {
        return '\\sout{' . $this->renderChildren($node) . '}';
    }

    protected function renderDel(ElementNode $node): string
    {
        return '\\sout{' . $this->renderChildren($node) . '}';
    }

    protected function renderIns(ElementNode $node): string
    {
        return '\\underline{' . $this->renderChildren($node) . '}';
    }

    protected function renderBreak(ElementNode $node): string
    {
        return "\\newline\n";
    }

    // ---------------------------------------------------------------
    // Styling — pass through text (LaTeX has limited color support
    // without additional packages)
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
    // Alignment
    // ---------------------------------------------------------------

    protected function renderCenter(ElementNode $node): string
    {
        return "\\begin{center}\n" . $this->renderChildren($node) . "\n\\end{center}\n";
    }

    protected function renderLeft(ElementNode $node): string
    {
        return "\\begin{flushleft}\n" . $this->renderChildren($node) . "\n\\end{flushleft}\n";
    }

    protected function renderRight(ElementNode $node): string
    {
        return "\\begin{flushright}\n" . $this->renderChildren($node) . "\n\\end{flushright}\n";
    }

    protected function renderJustify(ElementNode $node): string
    {
        // LaTeX justifies by default
        return $this->renderChildren($node);
    }

    // ---------------------------------------------------------------
    // Block elements
    // ---------------------------------------------------------------

    protected function renderHeading(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $level = max(1, min(6, (int) ($attrs['level'] ?? 1)));

        $commands = [
            1 => '\\part{',
            2 => '\\section{',
            3 => '\\subsection{',
            4 => '\\subsubsection{',
            5 => '\\paragraph{',
            6 => '\\subparagraph{',
        ];

        return $commands[$level] . $this->renderChildren($node) . "}\n";
    }

    protected function renderHoriz(ElementNode $node): string
    {
        return "\n\\noindent\\rule{\\textwidth}{1pt}\n";
    }

    protected function renderCode(ElementNode $node): string
    {
        $content = $this->renderChildrenRaw($node);

        return "\\begin{verbatim}\n" . $content . "\n\\end{verbatim}\n";
    }

    protected function renderPreformatted(ElementNode $node): string
    {
        $content = $this->renderChildrenRaw($node);

        return "\\begin{verbatim}\n" . $content . "\n\\end{verbatim}\n";
    }

    protected function renderRaw(ElementNode $node): string
    {
        $content = $this->renderChildrenRaw($node);

        return $content;
    }

    protected function renderBlockquote(ElementNode $node): string
    {
        return "\\begin{quote}\n" . $this->renderChildren($node) . "\n\\end{quote}\n";
    }

    protected function renderParagraph(ElementNode $node): string
    {
        return $this->renderChildren($node) . "\n\n";
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

        if ($text !== '' && $text !== $this->escapeLatex($href)) {
            return $text . '\\footnote{' . $this->escapeLatex($href) . '}';
        }

        return '\\url{' . $href . '}';
    }

    protected function renderEmail(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $email = $attrs['email'] ?? '';
        $text = $this->renderChildren($node);

        if ($text !== '' && $text !== $this->escapeLatex($email)) {
            return $text . '\\footnote{' . $this->escapeLatex($email) . '}';
        }

        return '\\url{mailto:' . $email . '}';
    }

    protected function renderWikilink(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $page = $attrs['page'] ?? '';
        $text = $this->renderChildren($node);

        if ($text !== '') {
            return $text;
        }

        return $this->escapeLatex($page);
    }

    protected function renderFreelink(ElementNode $node): string
    {
        return $this->renderWikilink($node);
    }

    protected function renderPhplookup(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $function = $attrs['function'] ?? '';
        $text = $this->renderChildren($node);

        return $text . '\\footnote{https://www.php.net/' . $this->escapeLatex($function) . '}';
    }

    // ---------------------------------------------------------------
    // Images
    // ---------------------------------------------------------------

    protected function renderImage(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $src = $attrs['src'] ?? '';

        return '\\includegraphics{' . $this->escapeLatex($src) . '}';
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

        $isBullet = !in_array($type, ['1', 'number', 'a', 'A'], true);
        $env = $isBullet ? 'itemize' : 'enumerate';

        $content = $this->renderListChildren($node);

        array_pop($this->listTypeStack);
        $this->listDepth--;

        return "\\begin{" . $env . "}\n" . $content . "\\end{" . $env . "}\n";
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

        return "\\item " . $content . "\n";
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
    // Tables
    // ---------------------------------------------------------------

    protected function renderTable(ElementNode $node): string
    {
        // Count columns from first row
        $cols = 0;
        foreach ($node->getChildren() as $child) {
            if ($child instanceof ElementNode && $child->getName() === 'row') {
                $cellCount = 0;
                foreach ($child->getChildren() as $cell) {
                    if ($cell instanceof ElementNode && $cell->getName() === 'cell') {
                        $cellCount++;
                    }
                }
                $cols = max($cols, $cellCount);
                break;
            }
        }

        if ($cols === 0) {
            $cols = 1;
        }

        $colSpec = str_repeat('|l', $cols) . '|';
        $output = "\\begin{tabular}{" . $colSpec . "}\n";
        $output .= "\\hline\n";

        foreach ($node->getChildren() as $child) {
            if ($child instanceof ElementNode && $child->getName() === 'row') {
                $output .= $this->renderRow($child);
            }
        }

        $output .= "\\end{tabular}\n";

        return $output;
    }

    protected function renderRow(ElementNode $node): string
    {
        $cells = [];
        foreach ($node->getChildren() as $child) {
            if ($child instanceof ElementNode && $child->getName() === 'cell') {
                $cells[] = $this->renderChildren($child);
            }
        }

        return implode(' & ', $cells) . " \\\\\n\\hline\n";
    }

    // ---------------------------------------------------------------
    // Definition lists
    // ---------------------------------------------------------------

    protected function renderDeflist(ElementNode $node): string
    {
        return "\\begin{description}\n" . $this->renderChildren($node) . "\\end{description}\n";
    }

    protected function renderDefterm(ElementNode $node): string
    {
        return '\\item[' . $this->renderChildren($node) . '] ';
    }

    protected function renderDefdef(ElementNode $node): string
    {
        return $this->renderChildren($node) . "\n";
    }

    // ---------------------------------------------------------------
    // Revision marks
    // ---------------------------------------------------------------

    protected function renderReviseDel(ElementNode $node): string
    {
        return '\\sout{' . $this->renderChildren($node) . '}';
    }

    protected function renderReviseIns(ElementNode $node): string
    {
        return '\\underline{' . $this->renderChildren($node) . '}';
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
        return "\\tableofcontents\n";
    }

    protected function renderAnchor(ElementNode $node): string
    {
        $attrs = $node->getAttributes();
        $name = $attrs['name'] ?? '';

        return '\\label{' . $this->escapeLatex($name) . '}';
    }
}
