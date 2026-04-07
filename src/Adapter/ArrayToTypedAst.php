<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Adapter;

use Horde\Text\Wiki\Node\DocumentNode;
use Horde\Text\Wiki\Node\ElementNode;
use Horde\Text\Wiki\Node\TextNode;

/**
 * Adapter: Array-based AST → Typed AST
 *
 * Converts legacy array-based document trees from old engines
 * (CowikiEngine, MediawikiEngine, etc.) into typed AST
 * (DocumentNode/ElementNode/TextNode) for modern renderers.
 *
 * This enables gradual migration from array-based to typed AST
 * while keeping a single modern renderer.
 *
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class ArrayToTypedAst
{
    /**
     * Map legacy short tag names to canonical semantic names
     */
    private const NAME_MAP = [
        'b'     => 'bold',
        'i'     => 'italic',
        'u'     => 'underline',
        's'     => 'strike',
        'sup'   => 'superscript',
        'sub'   => 'subscript',
        'hr'    => 'horiz',
        'img'   => 'image',
        '*'     => 'listitem',
        'quote' => 'blockquote',
    ];
    /**
     * Convert array-based AST to typed AST
     *
     * Array format from old engines (varies by engine):
     * - Simple text: string
     * - Element: ['type' => 'tag_name', 'text' => '...', 'attr' => [...], 'children' => [...]]
     * - Mixed: array of strings and element arrays
     *
     * @param mixed $arrayAst Array-based AST from old engine
     *
     * @return DocumentNode Typed AST root
     */
    public function convert(mixed $arrayAst): DocumentNode
    {
        $document = new DocumentNode();

        if (is_string($arrayAst)) {
            // Plain string - wrap in text node
            $document->addChild(new TextNode($arrayAst));
        } elseif (is_array($arrayAst)) {
            $this->convertChildren($arrayAst, $document);
        }

        return $document;
    }

    /**
     * Convert array children to typed nodes
     *
     * @param array       $children Array of child nodes
     * @param DocumentNode|ElementNode $parent   Parent node to add to
     *
     * @return void
     */
    protected function convertChildren(array $children, DocumentNode|ElementNode $parent): void
    {
        foreach ($children as $child) {
            if (is_string($child)) {
                // Text node
                $parent->addChild(new TextNode($child));
            } elseif (is_array($child)) {
                // Element or nested structure
                $node = $this->convertElement($child);
                if ($node !== null) {
                    $parent->addChild($node);
                }
            }
        }
    }

    /**
     * Convert array element to typed ElementNode
     *
     * Array format varies by engine, but common patterns:
     * - ['type' => 'tag_name', 'text' => '...', 'attr' => [...]]
     * - ['name' => 'tag_name', 'content' => '...', 'attributes' => [...]]
     *
     * @param array $element Array element
     *
     * @return ElementNode|TextNode|null Typed node or null if unrecognized
     */
    protected function convertElement(array $element): ElementNode|TextNode|null
    {
        // Try 'type' field (common pattern)
        $name = $element['type'] ?? $element['name'] ?? null;

        if ($name === null) {
            // No recognizable tag name - treat as text if 'text' field exists
            if (isset($element['text'])) {
                return new TextNode($element['text']);
            }
            return null;
        }

        // Extract attributes
        $attrs = $element['attr'] ?? $element['attributes'] ?? [];

        // Normalize legacy tag names to canonical names
        $canonicalName = self::NAME_MAP[$name] ?? $name;

        // Create element node
        $node = new ElementNode($canonicalName, $attrs);

        // Handle text content
        if (isset($element['text']) && is_string($element['text'])) {
            $node->addChild(new TextNode($element['text']));
        }

        // Handle nested children
        if (isset($element['children']) && is_array($element['children'])) {
            $this->convertChildren($element['children'], $node);
        }

        // Handle 'content' field (alternative to 'children')
        if (isset($element['content'])) {
            if (is_string($element['content'])) {
                $node->addChild(new TextNode($element['content']));
            } elseif (is_array($element['content'])) {
                $this->convertChildren($element['content'], $node);
            }
        }

        return $node;
    }
}
