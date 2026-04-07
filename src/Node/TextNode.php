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
 * Text node (plain text content)
 *
 * Represents plain text content between markup tags.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class TextNode implements Node
{
    /**
     * Constructor
     *
     * @param string $text Text content
     */
    public function __construct(
        private string $text,
    ) {}

    /**
     * Get node name
     *
     * @return string Always 'text'
     */
    public function getName(): string
    {
        return 'text';
    }

    /**
     * Get text content
     *
     * @return string Text content
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * Append text to this node
     *
     * Used to merge consecutive text nodes.
     *
     * @param string $text Text to append
     *
     * @return void
     */
    public function appendText(string $text): void
    {
        $this->text .= $text;
    }

    /**
     * Get child nodes
     *
     * Text nodes have no children.
     *
     * @return array Always empty array
     */
    public function getChildren(): array
    {
        return [];
    }

    /**
     * Add a child node
     *
     * Text nodes cannot have children. This is a no-op.
     *
     * @param Node $child Child node (ignored)
     *
     * @return void
     */
    public function addChild(Node $child): void
    {
        // Text nodes cannot have children
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
        return $visitor->visitText($this);
    }
}
