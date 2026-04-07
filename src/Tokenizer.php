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
 * Tokenizer interface: Input text → Token stream
 *
 * Tokenizers convert raw markup text into a stream of typed tokens
 * for structure building.
 *
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
interface Tokenizer
{
    /**
     * Tokenize input text
     *
     * Converts markup text into a stream of tokens. Returns an iterable
     * (typically a generator) for memory efficiency on large documents.
     *
     * Tokenizer should:
     * - Identify tags ([b], [/b], [url=...])
     * - Extract text between tags
     * - Track character positions
     * - Extract tag attributes
     * - Identify malformed tags (treat as text)
     *
     * @param string $text Input markup text
     *
     * @return iterable<Token> Stream of tokens (can be generator for efficiency)
     */
    public function tokenize(string $text): iterable;
}
