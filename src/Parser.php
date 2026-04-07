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
 * Parser interface: Input format → Abstract Syntax Tree
 *
 * Parsers convert markup text (BBCode, Mediawiki, etc.) into a structured
 * document tree for validation and rendering.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
interface Parser
{
    /**
     * Parse input text into an Abstract Syntax Tree
     *
     * Converts markup text into a validated document tree. Parser should:
     * - Tokenize input
     * - Build tree structure
     * - Validate nesting
     * - Auto-close unclosed tags
     * - Reject invalid attributes
     *
     * @param string $text Input in parser's format (BBCode, Mediawiki, etc.)
     *
     * @return DocumentNode Root of parsed document tree
     *
     * @throws ParseException If parsing fails catastrophically (rare)
     */
    public function parse(string $text): DocumentNode;

    /**
     * Get the markup format this parser handles
     *
     * @return string Format identifier ('bbcode', 'mediawiki', 'cowiki', etc.)
     */
    public function getFormat(): string;
}
