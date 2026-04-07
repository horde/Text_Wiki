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
 * Base node interface for document tree
 *
 * All nodes in the document tree implement this interface.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
interface Node
{
    /**
     * Get node name/type
     *
     * @return string Node name ('document', 'b', 'text', etc.)
     */
    public function getName(): string;

    /**
     * Get child nodes
     *
     * @return array<Node> Array of child nodes
     */
    public function getChildren(): array;

    /**
     * Add a child node
     *
     * @param Node $child Child node to add
     *
     * @return void
     */
    public function addChild(Node $child): void;

    /**
     * Accept a visitor (visitor pattern)
     *
     * Allows renderers to traverse the tree and generate output.
     *
     * @param NodeVisitor $visitor Visitor to accept
     *
     * @return string Rendered output from visitor
     */
    public function accept(NodeVisitor $visitor): string;
}
