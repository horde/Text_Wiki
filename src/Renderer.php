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
 * Renderer interface: Abstract Syntax Tree → Output format
 *
 * Renderers traverse the document tree and generate output in various
 * formats (XHTML, Plain, LaTeX, etc.).
 *
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
interface Renderer
{
    /**
     * Render an Abstract Syntax Tree to output format
     *
     * Traverses the document tree and generates output. Renderer should:
     * - Walk tree via visitor pattern
     * - Escape output appropriately
     * - Handle all node types
     * - Produce valid output
     *
     * @param DocumentNode $document Parsed document tree
     *
     * @return string Output in renderer's format (XHTML, Plain, LaTeX, etc.)
     */
    public function render(DocumentNode $document): string;

    /**
     * Get the output format this renderer produces
     *
     * @return string Format identifier ('xhtml', 'plain', 'latex', etc.)
     */
    public function getFormat(): string;
}
