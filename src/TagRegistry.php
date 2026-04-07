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
 * Tag registry interface: Tag definition lookup
 *
 * Registry of all known tags and their behavior/validation rules.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
interface TagRegistry
{
    /**
     * Register a tag definition
     *
     * Adds a tag to the registry. If tag already exists, replaces it.
     *
     * @param TagDefinition $definition Tag definition to register
     *
     * @return void
     */
    public function register(TagDefinition $definition): void;

    /**
     * Get tag definition by name
     *
     * Returns the definition for a tag, or null if not registered.
     *
     * @param string $name Tag name (case-insensitive)
     *
     * @return TagDefinition|null Tag definition or null if not found
     */
    public function get(string $name): ?TagDefinition;

    /**
     * Check if tag is registered
     *
     * @param string $name Tag name (case-insensitive)
     *
     * @return bool True if tag is registered
     */
    public function has(string $name): bool;
}
