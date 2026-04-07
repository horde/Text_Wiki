<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki;

use Horde\Text\Wiki\Node\DocumentNode;
use Horde\Text\Wiki\Node\ElementNode;
use Horde\Text\Wiki\Node\TextNode;

/**
 * Generic structure builder: Token stream → Typed AST
 *
 * Format-agnostic builder that converts token streams into validated
 * document trees. Uses TagRegistry for validation rules.
 *
 * Handles:
 * - Nesting validation
 * - Auto-closing unclosed tags
 * - Invalid tags as text
 * - Verbatim content ([code])
 * - Self-closing tags ([*])
 *
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class GenericStructureBuilder implements StructureBuilder
{
    /**
     * Tag registry for validation rules
     *
     * @var TagRegistry
     */
    private TagRegistry $registry;

    /**
     * Stack of open elements (for nesting)
     *
     * @var array<ElementNode>
     */
    private array $stack = [];

    /**
     * Constructor
     *
     * @param TagRegistry $registry Tag registry with validation rules
     */
    public function __construct(TagRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Build document tree from token stream
     *
     * @param iterable<Token> $tokens Token stream from tokenizer
     *
     * @return DocumentNode Document root with validated structure
     */
    public function build(iterable $tokens): DocumentNode
    {
        $document = new DocumentNode();
        $this->stack = [$document];

        foreach ($tokens as $token) {
            match ($token->type) {
                TokenType::OPEN_TAG => $this->handleOpenTag($token),
                TokenType::CLOSE_TAG => $this->handleCloseTag($token),
                TokenType::TEXT => $this->handleText($token),
                TokenType::NEWLINE => $this->handleNewline($token),
            };
        }

        // Auto-close any remaining open tags
        $this->closeAllOpenTags();

        return $document;
    }

    /**
     * Handle opening tag token
     *
     * @param Token $token Opening tag token
     *
     * @return void
     */
    protected function handleOpenTag(Token $token): void
    {
        $tagName = $token->value;
        $attributes = $token->attributes;

        // Look up tag definition
        $tagDef = $this->registry->get($tagName);

        if ($tagDef === null) {
            // Unknown tag - use original text if available
            $text = $token->originalText !== null
                ? '[' . $token->originalText . ']'
                : '[' . $tagName . $this->formatAttributes($attributes) . ']';
            $this->addText($text);
            return;
        }

        // Validate attributes
        if (!$tagDef->validateAttributes($attributes)) {
            // Invalid attributes - use original text
            $text = $token->originalText !== null
                ? '[' . $token->originalText . ']'
                : '[' . $tagName . $this->formatAttributes($attributes) . ']';
            $this->addText($text);
            return;
        }

        // Check if parent can contain this tag
        $parent = $this->getCurrentParent();
        if (!$this->canAddToParent($parent, $tagDef)) {
            // Invalid nesting - auto-close parent and try again
            $this->autoCloseInvalidParent($tagDef);
            $parent = $this->getCurrentParent();
        }

        // Special case: listitem implicitly closes previous listitem
        // Do this BEFORE getting parent, so parent is [list] not previous listitem
        if ($tagName === 'listitem') {
            $this->implicitlyClosePreviousListItem();
            $parent = $this->getCurrentParent();
        }

        // Create element node
        $element = new ElementNode($tagName, $attributes);

        // Check if verbatim content
        if (!$tagDef->shouldParseContent()) {
            $element->setVerbatim(true);
        }

        // Add to parent
        $parent->addChild($element);

        // Push to stack if requires closing
        if ($tagDef->requiresClosing()) {
            $this->stack[] = $element;
        }
    }

    /**
     * Handle closing tag token
     *
     * @param Token $token Closing tag token
     *
     * @return void
     */
    protected function handleCloseTag(Token $token): void
    {
        $tagName = $token->value;

        // Find matching open tag in stack
        $matchIndex = $this->findOpenTag($tagName);

        if ($matchIndex === -1) {
            // No matching open tag - use original text if available
            $text = $token->originalText !== null
                ? '[' . $token->originalText . ']'
                : '[/' . $tagName . ']';
            $this->addText($text);
            return;
        }

        // Close all tags from match to top of stack
        $this->closeTagsUntil($matchIndex);
    }

    /**
     * Handle text token
     *
     * @param Token $token Text token
     *
     * @return void
     */
    protected function handleText(Token $token): void
    {
        $this->addText($token->value);
    }

    /**
     * Handle newline token
     *
     * @param Token $token Newline token
     *
     * @return void
     */
    protected function handleNewline(Token $token): void
    {
        $this->addText("\n");
    }

    /**
     * Add text to current parent
     *
     * Merges with previous text node if possible.
     *
     * @param string $text Text content
     *
     * @return void
     */
    protected function addText(string $text): void
    {
        if ($text === '') {
            return;
        }

        $parent = $this->getCurrentParent();
        $lastChild = $parent instanceof DocumentNode || $parent instanceof ElementNode
            ? $parent->getLastChild()
            : null;

        // Merge with previous text node
        if ($lastChild instanceof TextNode) {
            $lastChild->appendText($text);
        } else {
            $parent->addChild(new TextNode($text));
        }
    }

    /**
     * Get current parent node (top of stack)
     *
     * @return DocumentNode|ElementNode Current parent
     */
    protected function getCurrentParent(): DocumentNode|ElementNode
    {
        return $this->stack[count($this->stack) - 1];
    }

    /**
     * Check if tag can be added to parent
     *
     * @param DocumentNode|ElementNode $parent Parent node
     * @param TagDefinition            $tagDef Tag definition
     *
     * @return bool True if allowed
     */
    protected function canAddToParent(DocumentNode|ElementNode $parent, TagDefinition $tagDef): bool
    {
        // Document root accepts everything
        if ($parent instanceof DocumentNode) {
            return true;
        }

        // Get parent's tag definition
        $parentDef = $this->registry->get($parent->getName());
        if ($parentDef === null) {
            return true; // Unknown parent, allow
        }

        return $parentDef->canContain($tagDef);
    }

    /**
     * Auto-close parent tags that can't contain child
     *
     * @param TagDefinition $childDef Child tag trying to be added
     *
     * @return void
     */
    protected function autoCloseInvalidParent(TagDefinition $childDef): void
    {
        // Pop stack until we find a valid parent
        while (count($this->stack) > 1) {
            $parent = $this->getCurrentParent();

            if ($parent instanceof DocumentNode) {
                break; // Can't close document root
            }

            $parentDef = $this->registry->get($parent->getName());
            if ($parentDef === null) {
                break;
            }

            if ($parentDef->canContain($childDef)) {
                break; // Found valid parent
            }

            // Mark as auto-closed and pop
            $parent->setAutoClosed(true);
            array_pop($this->stack);
        }
    }

    /**
     * Find open tag in stack
     *
     * Returns index in stack, or -1 if not found.
     *
     * @param string $tagName Tag name to find
     *
     * @return int Stack index or -1
     */
    protected function findOpenTag(string $tagName): int
    {
        for ($i = count($this->stack) - 1; $i >= 1; $i--) {
            $node = $this->stack[$i];
            if ($node instanceof ElementNode && $node->getName() === $tagName) {
                return $i;
            }
        }
        return -1;
    }

    /**
     * Close all tags from matchIndex to top of stack
     *
     * @param int $matchIndex Index of matching tag in stack
     *
     * @return void
     */
    protected function closeTagsUntil(int $matchIndex): void
    {
        while (count($this->stack) > $matchIndex) {
            $closing = array_pop($this->stack);

            // Mark as auto-closed if not the matched tag
            if ($closing instanceof ElementNode && count($this->stack) > $matchIndex) {
                $closing->setAutoClosed(true);
            }
        }
    }

    /**
     * Close all remaining open tags at end of document
     *
     * @return void
     */
    protected function closeAllOpenTags(): void
    {
        while (count($this->stack) > 1) {
            $closing = array_pop($this->stack);
            if ($closing instanceof ElementNode) {
                $closing->setAutoClosed(true);
            }
        }
    }

    /**
     * Implicitly close previous listitem
     *
     * When a new listitem is encountered, the previous one should close.
     *
     * @return void
     */
    protected function implicitlyClosePreviousListItem(): void
    {
        // Only close a listitem that is at the top of the stack
        // (direct child of the current list). Don't reach past list
        // boundaries to close parent listitems.
        $top = $this->stack[count($this->stack) - 1] ?? null;
        if ($top instanceof ElementNode && $top->getName() === 'listitem') {
            array_pop($this->stack);
        }
    }

    /**
     * Format attributes for error messages
     *
     * @param array $attributes Attributes array
     *
     * @return string Formatted attributes
     */
    protected function formatAttributes(array $attributes): string
    {
        if (empty($attributes)) {
            return '';
        }

        $parts = [];
        foreach ($attributes as $key => $value) {
            $parts[] = $key . '="' . $value . '"';
        }

        return ' ' . implode(' ', $parts);
    }
}
