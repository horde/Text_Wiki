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

/**
 * Structure builder interface: Token stream → AST
 *
 * Builds document tree from token stream with validation and auto-correction.
 *
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
interface StructureBuilder
{
    /**
     * Build document tree from token stream
     *
     * Converts flat token stream into hierarchical document tree.
     *
     * Builder should:
     * - Maintain stack of open elements
     * - Validate nesting rules
     * - Auto-close unclosed tags
     * - Reject invalid attributes
     * - Handle mismatched tags gracefully
     * - Treat unknown/invalid tags as text
     *
     * @param iterable<Token> $tokens Token stream from tokenizer
     *
     * @return DocumentNode Document root with validated structure
     */
    public function build(iterable $tokens): DocumentNode;
}
