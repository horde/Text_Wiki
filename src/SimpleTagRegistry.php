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
 * Simple tag registry implementation
 *
 * Stores and retrieves tag definitions by name.
 *
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class SimpleTagRegistry implements TagRegistry
{
    /**
     * Registered tag definitions
     *
     * @var array<string, TagDefinition>
     */
    private array $tags = [];

    /**
     * Register a tag definition
     *
     * @param TagDefinition $definition Tag definition to register
     *
     * @return void
     */
    public function register(TagDefinition $definition): void
    {
        $this->tags[strtolower($definition->getName())] = $definition;
    }

    /**
     * Get tag definition by name
     *
     * @param string $name Tag name (case-insensitive)
     *
     * @return TagDefinition|null Tag definition or null if not found
     */
    public function get(string $name): ?TagDefinition
    {
        $key = strtolower($name);
        return $this->tags[$key] ?? null;
    }

    /**
     * Check if tag is registered
     *
     * @param string $name Tag name (case-insensitive)
     *
     * @return bool True if tag is registered
     */
    public function has(string $name): bool
    {
        return isset($this->tags[strtolower($name)]);
    }
}
