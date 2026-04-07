<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki;

use Horde\Text\Wiki\Node\{DocumentNode, ElementNode, TextNode};

/**
 * Node visitor interface for rendering
 *
 * Implements visitor pattern for traversing document tree and
 * generating output.
 *
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
interface NodeVisitor
{
    /**
     * Visit document root node
     *
     * @param DocumentNode $node Document root
     *
     * @return string Rendered output
     */
    public function visitDocument(DocumentNode $node): string;

    /**
     * Visit element node (tag)
     *
     * @param ElementNode $node Element node (bold, url, list, etc.)
     *
     * @return string Rendered output
     */
    public function visitElement(ElementNode $node): string;

    /**
     * Visit text node
     *
     * @param TextNode $node Text content node
     *
     * @return string Rendered output (usually escaped)
     */
    public function visitText(TextNode $node): string;
}
