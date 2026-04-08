<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Node;

use Horde\Text\Wiki\NodeVisitor;

/**
 * Element node (markup tag)
 *
 * Represents a markup tag like [b], [url], [list], etc.
 *
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class ElementNode implements Node
{
    /** @var array<Node> Child nodes */
    private array $children = [];

    /** @var bool Whether this tag was auto-closed */
    private bool $autoClosed = false;

    /** @var bool Whether content should be treated as verbatim (no parsing) */
    private bool $verbatim = false;

    /**
     * Constructor
     *
     * @param string $name       Tag name (lowercase, e.g., 'b', 'url')
     * @param array  $attributes Tag attributes (['attr' => 'value'])
     */
    public function __construct(
        private readonly string $name,
        private array $attributes = [],
    ) {}

    /**
     * Get tag name
     *
     * @return string Tag name (e.g., 'b', 'url', 'list')
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get attributes
     *
     * @return array Tag attributes
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Set an attribute
     *
     * @param string $key   Attribute name
     * @param mixed  $value Attribute value
     *
     * @return void
     */
    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Get child nodes
     *
     * @return array<Node> Array of child nodes
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * Add a child node
     *
     * @param Node $child Child node to add
     *
     * @return void
     */
    public function addChild(Node $child): void
    {
        $this->children[] = $child;
    }

    /**
     * Remove a child node
     *
     * @param Node $child Child node to remove
     *
     * @return void
     */
    public function removeChild(Node $child): void
    {
        $this->children = array_values(
            array_filter($this->children, fn(Node $c) => $c !== $child)
        );
    }

    /**
     * Get last child node
     *
     * @return Node|null Last child or null if no children
     */
    public function getLastChild(): ?Node
    {
        if (empty($this->children)) {
            return null;
        }
        return $this->children[array_key_last($this->children)];
    }

    /**
     * Check if this tag was auto-closed
     *
     * @return bool True if tag was auto-closed by parser
     */
    public function isAutoClosed(): bool
    {
        return $this->autoClosed;
    }

    /**
     * Mark this tag as auto-closed
     *
     * Called by structure builder when closing unclosed tags.
     *
     * @param bool $autoClosed Whether tag was auto-closed
     *
     * @return void
     */
    public function setAutoClosed(bool $autoClosed): void
    {
        $this->autoClosed = $autoClosed;
    }

    /**
     * Check if content should be treated as verbatim
     *
     * For tags like [code] where inner content should not be parsed.
     *
     * @return bool True if content is verbatim
     */
    public function isVerbatim(): bool
    {
        return $this->verbatim;
    }

    /**
     * Set verbatim mode
     *
     * @param bool $verbatim Whether content is verbatim
     *
     * @return void
     */
    public function setVerbatim(bool $verbatim): void
    {
        $this->verbatim = $verbatim;
    }

    /**
     * Accept a visitor (visitor pattern)
     *
     * @param NodeVisitor $visitor Visitor to accept
     *
     * @return string Rendered output from visitor
     */
    public function accept(NodeVisitor $visitor): string
    {
        return $visitor->visitElement($this);
    }
}
