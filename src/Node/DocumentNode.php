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
 * Document root node
 *
 * Root of the document tree, contains all other nodes.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class DocumentNode implements Node
{
    /** @var array<Node> Child nodes */
    private array $children = [];

    /**
     * Get node name
     *
     * @return string Always 'document'
     */
    public function getName(): string
    {
        return 'document';
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
     * Accept a visitor (visitor pattern)
     *
     * @param NodeVisitor $visitor Visitor to accept
     *
     * @return string Rendered output from visitor
     */
    public function accept(NodeVisitor $visitor): string
    {
        return $visitor->visitDocument($this);
    }
}
