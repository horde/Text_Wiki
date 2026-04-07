<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki;

/**
 * Tag definition interface: Validation rules for tags
 *
 * Defines behavior and validation rules for a single markup tag.
 *
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
interface TagDefinition
{
    /**
     * Get tag name
     *
     * @return string Tag name (lowercase, e.g. 'b', 'url', 'list')
     */
    public function getName(): string;

    /**
     * Get tag type
     *
     * @return TagType INLINE, BLOCK, or MIXED
     */
    public function getType(): TagType;

    /**
     * Check if this tag can contain a child tag
     *
     * Validates nesting rules. For example:
     * - Blocks generally can't contain other blocks
     * - [list] can only contain [*] and [list]
     * - [code] can't contain any tags
     *
     * @param TagDefinition $child Child tag to check
     *
     * @return bool True if child can be nested inside this tag
     */
    public function canContain(TagDefinition $child): bool;

    /**
     * Validate tag attributes
     *
     * Checks if attribute values are valid for this tag.
     * For example:
     * - [url] validates URL scheme
     * - [color] validates color format
     *
     * @param array $attrs Attributes from token (['attr' => 'value'])
     *
     * @return bool True if attributes are valid
     */
    public function validateAttributes(array $attrs): bool;

    /**
     * Check if tag content should be parsed
     *
     * Returns false for verbatim tags like [code] where inner
     * content should not be parsed for nested tags.
     *
     * @return bool True if content should be parsed, false for verbatim
     */
    public function shouldParseContent(): bool;

    /**
     * Check if tag requires closing tag
     *
     * Returns false for self-closing tags (e.g., [*] list items).
     * Most tags return true.
     *
     * @return bool True if closing tag required
     */
    public function requiresClosing(): bool;
}
