<?php

declare(strict_types=1);

/**
 * Copyright 2025-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Commonmark;

use Horde\Text\Wiki\Node\DocumentNode;
use Horde\Text\Wiki\Node\ElementNode;
use Horde\Text\Wiki\Node\TextNode;
use Horde\Text\Wiki\Parser;
use Horde\Text\Wiki\TagRegistry;
use Closure;

/**
 * Markdown parser (CommonMark / GFM)
 *
 * Implements the CommonMark specification with optional GFM extensions.
 * Uses a two-phase parsing algorithm:
 * 1. Block parsing (line-by-line state machine)
 * 2. Inline parsing (delimiter-stack-based)
 *
 * Defaults to GFM mode. Pass MarkdownTagRegistry::commonmark()
 * for strict CommonMark-only parsing.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class MarkdownParser implements Parser
{
    private TagRegistry $registry;

    public function __construct(?TagRegistry $registry = null)
    {
        $this->registry = $registry ?? MarkdownTagRegistry::gfm();
    }

    /**
     * Parse CommonMark/GFM text into typed AST
     *
     * @param string $text Markdown input text
     *
     * @return DocumentNode Document root with block structure and parsed inlines
     */
    public function parse(string $text): DocumentNode
    {
        // Phase 1: Block parsing
        $blockParser = new BlockParser($this->registry);
        $context = $blockParser->parse($text);

        // Phase 2: Inline parsing
        $inlineParser = new InlineParser($context->getReferenceMap(), $this->registry);

        // GFM: Pre-process before inline parsing
        if ($this->registry->has('strike')) {
            $this->filterDisallowedHtmlBlocks($context->getDocument());
            $this->preprocessTaskListItems($context->getDocument());
        }

        $this->parseInlines($context->getDocument(), $inlineParser);

        return $context->getDocument();
    }

    /**
     * Get format identifier
     *
     * @return string 'markdown'
     */
    public function getFormat(): string
    {
        return 'markdown';
    }

    /**
     * Walk the AST and parse inline content in leaf blocks
     */
    private function parseInlines(DocumentNode $document, InlineParser $inlineParser): void
    {
        foreach ($document->getChildren() as $child) {
            $this->parseNodeInlines($child, $inlineParser);
        }
    }

    private function parseNodeInlines(mixed $node, InlineParser $inlineParser): void
    {
        if (!$node instanceof ElementNode) {
            return;
        }

        $name = $node->getName();

        // Leaf blocks whose content should be inline-parsed
        if (in_array($name, ['paragraph', 'heading'], true)) {
            $this->replaceTextWithInlines($node, $inlineParser);
            return;
        }

        // GFM table cells also get inline-parsed
        if ($name === 'cell') {
            $this->replaceTextWithInlines($node, $inlineParser);
            return;
        }

        // Verbatim blocks (code, htmlblock) — skip inline parsing
        if ($node->isVerbatim()) {
            return;
        }

        // Container blocks — recurse into children
        foreach ($node->getChildren() as $child) {
            $this->parseNodeInlines($child, $inlineParser);
        }
    }

    /**
     * Replace TextNode children with parsed inline nodes,
     * preserving any non-TextNode children already present
     */
    private function replaceTextWithInlines(ElementNode $node, InlineParser $inlineParser): void
    {
        $children = $node->getChildren();
        $preNodes = []; // Non-text nodes before text
        $text = '';
        $postNodes = []; // Non-text nodes after text
        $seenText = false;

        foreach ($children as $child) {
            if ($child instanceof TextNode) {
                $text .= $child->getText();
                $seenText = true;
            } elseif (!$seenText) {
                $preNodes[] = $child;
            } else {
                $postNodes[] = $child;
            }
        }

        if ($text === '') {
            return;
        }

        $inlines = $inlineParser->parse($text);

        $clearChildren = Closure::bind(function () {
            $this->children = [];
        }, $node, ElementNode::class);
        $clearChildren();

        foreach ($preNodes as $pre) {
            $node->addChild($pre);
        }
        foreach ($inlines as $inline) {
            $node->addChild($inline);
        }
        foreach ($postNodes as $post) {
            $node->addChild($post);
        }
    }

    /**
     * Filter GFM disallowed raw HTML tags in htmlblock nodes
     *
     * Replaces `<` with `&lt;` for specific tags per GFM spec.
     * Applied at AST level so the renderer stays format-agnostic.
     */
    private function filterDisallowedHtmlBlocks(DocumentNode $document): void
    {
        foreach ($document->getChildren() as $child) {
            $this->filterDisallowedHtmlNode($child);
        }
    }

    private function filterDisallowedHtmlNode(mixed $node): void
    {
        if (!$node instanceof ElementNode) {
            return;
        }

        if ($node->getName() === 'htmlblock') {
            foreach ($node->getChildren() as $child) {
                if ($child instanceof TextNode) {
                    $text = $child->getText();
                    $filtered = preg_replace(
                        '/<(\/?(?:title|textarea|style|xmp|iframe|noembed|noframes|script|plaintext)(?:\s|>|\/?>))/i',
                        '&lt;$1',
                        $text
                    );
                    if ($filtered !== null && $filtered !== $text) {
                        $child->setText($filtered);
                    }
                }
            }
            return;
        }

        foreach ($node->getChildren() as $child) {
            $this->filterDisallowedHtmlNode($child);
        }
    }

    /**
     * Pre-process task list items: detect [ ]/[x] markers in paragraph text
     * and replace with checkbox HTML before inline parsing runs
     */
    private function preprocessTaskListItems(DocumentNode $document): void
    {
        foreach ($document->getChildren() as $child) {
            $this->preprocessTaskListNode($child);
        }
    }

    private function preprocessTaskListNode(mixed $node): void
    {
        if (!$node instanceof ElementNode) {
            return;
        }

        if ($node->getName() === 'listitem') {
            $children = $node->getChildren();
            if (!empty($children) && $children[0] instanceof ElementNode
                && $children[0]->getName() === 'paragraph') {
                $para = $children[0];
                $paraChildren = $para->getChildren();
                if (!empty($paraChildren) && $paraChildren[0] instanceof TextNode) {
                    $text = $paraChildren[0]->getText();
                    $checkboxHtml = null;
                    if (str_starts_with($text, '[ ] ')) {
                        $checkboxHtml = '<input disabled="" type="checkbox"> ';
                        $remaining = substr($text, 4);
                    } elseif (str_starts_with($text, '[x] ') || str_starts_with($text, '[X] ')) {
                        $checkboxHtml = '<input checked="" disabled="" type="checkbox"> ';
                        $remaining = substr($text, 4);
                    }

                    if ($checkboxHtml !== null) {
                        // Replace the paragraph's first text node with checkbox + remaining
                        $clearChildren = Closure::bind(function () {
                            $this->children = [];
                        }, $para, ElementNode::class);
                        $clearChildren();

                        $checkbox = new ElementNode('htmlinline');
                        $checkbox->setVerbatim(true);
                        $checkbox->addChild(new TextNode($checkboxHtml));
                        $para->addChild($checkbox);
                        if ($remaining !== '') {
                            $para->addChild(new TextNode($remaining));
                        }
                        for ($i = 1; $i < count($paraChildren); $i++) {
                            $para->addChild($paraChildren[$i]);
                        }
                    }
                }
            }
        }

        foreach ($node->getChildren() as $child) {
            $this->preprocessTaskListNode($child);
        }
    }
}
