<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\BBCode;

use Horde\Text\Wiki\TagDefinition;
use Horde\Text\Wiki\TagType;

/**
 * Base implementation for BBCode tag definitions
 *
 * Provides default implementations for common tag behaviors.
 * Subclasses override specific methods for custom behavior.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
abstract class AbstractTagDefinition implements TagDefinition
{
    /**
     * Constructor
     *
     * @param string  $name Tag name (lowercase)
     * @param TagType $type Tag type (INLINE, BLOCK, MIXED)
     */
    public function __construct(
        private readonly string $name,
        private readonly TagType $type,
    ) {}

    /**
     * Get tag name
     *
     * @return string Tag name (lowercase)
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get tag type
     *
     * @return TagType INLINE, BLOCK, or MIXED
     */
    public function getType(): TagType
    {
        return $this->type;
    }

    /**
     * Check if this tag can contain a child tag
     *
     * Default: blocks can't contain blocks, inlines can contain anything.
     * Override for specific nesting rules (e.g., [list] only allows [*]).
     *
     * @param TagDefinition $child Child tag to check
     *
     * @return bool True if child can be nested
     */
    public function canContain(TagDefinition $child): bool
    {
        // Block tags can't contain other block tags (prevents nesting <div> in <div>)
        if ($this->type === TagType::BLOCK && $child->getType() === TagType::BLOCK) {
            return false;
        }

        // All other combinations allowed by default
        return true;
    }

    /**
     * Validate tag attributes
     *
     * Default: no validation (all attributes accepted).
     * Override for tags that need attribute validation (e.g., [url], [color]).
     *
     * @param array $attrs Attributes from token
     *
     * @return bool True if attributes are valid
     */
    public function validateAttributes(array $attrs): bool
    {
        return true;
    }

    /**
     * Check if tag content should be parsed
     *
     * Default: true (parse nested tags).
     * Override for verbatim tags like [code].
     *
     * @return bool True if content should be parsed
     */
    public function shouldParseContent(): bool
    {
        return true;
    }

    /**
     * Check if tag requires closing tag
     *
     * Default: true (requires closing).
     * Override for self-closing tags like [*].
     *
     * @return bool True if closing tag required
     */
    public function requiresClosing(): bool
    {
        return true;
    }
}
