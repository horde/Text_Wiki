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
 * Token produced by tokenizers
 *
 * Immutable data structure representing a single token from the input stream.
 *
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class Token
{
    /**
     * Constructor
     *
     * @param TokenType   $type         Token type
     * @param string      $value        Token value (tag name, text content, etc.)
     * @param int         $position     Character position in source text
     * @param array       $attributes   Tag attributes (for OPEN_TAG tokens)
     * @param string|null $originalText Original tag text between [ and ] (for error messages)
     */
    public function __construct(
        public readonly TokenType $type,
        public readonly string $value,
        public readonly int $position,
        public readonly array $attributes = [],
        public readonly ?string $originalText = null,
    ) {}

    /**
     * Get token type
     *
     * @return TokenType
     */
    public function getType(): TokenType
    {
        return $this->type;
    }

    /**
     * Get token value
     *
     * For TEXT tokens: the text content
     * For tag tokens: the tag name (lowercase)
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Get character position in source
     *
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * Get attributes
     *
     * For OPEN_TAG tokens with attributes like [url=...], returns
     * ['attr' => 'value']. Empty array for other token types.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
