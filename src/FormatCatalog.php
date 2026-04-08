<?php

declare(strict_types=1);

/**
 * Copyright 2025-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki;

/**
 * Format catalog interface: discovery and lookup of parsers and renderers
 *
 * A catalog provides a central registry of available input parsers and
 * output renderers. Wiki applications use it to list available formats,
 * find conversion targets, and resolve format names to concrete objects.
 *
 * Registering a parser/renderer with the same format replaces the
 * existing one, enabling external code to override built-in formats.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
interface FormatCatalog
{
    /**
     * Register a parser
     *
     * The parser's getFormat() determines the key. Replaces any
     * previously registered parser for the same format.
     *
     * @param Parser $parser Parser to register
     */
    public function registerParser(Parser $parser): void;

    /**
     * Register a renderer
     *
     * The renderer's getFormat() determines the key. Replaces any
     * previously registered renderer for the same format.
     *
     * @param Renderer $renderer Renderer to register
     */
    public function registerRenderer(Renderer $renderer): void;

    /**
     * Get a parser by format name
     *
     * @param string $format Format identifier (case-insensitive)
     *
     * @return Parser The parser for the requested format
     *
     * @throws GenericTextWikiException If no parser is registered for the format
     */
    public function getParser(string $format): Parser;

    /**
     * Get a renderer by format name
     *
     * @param string $format Format identifier (case-insensitive)
     *
     * @return Renderer The renderer for the requested format
     *
     * @throws GenericTextWikiException If no renderer is registered for the format
     */
    public function getRenderer(string $format): Renderer;

    /**
     * Check if a parser is registered for a format
     *
     * @param string $format Format identifier (case-insensitive)
     */
    public function hasParser(string $format): bool;

    /**
     * Check if a renderer is registered for a format
     *
     * @param string $format Format identifier (case-insensitive)
     */
    public function hasRenderer(string $format): bool;

    /**
     * List all registered parser format names
     *
     * @return list<string> Format identifiers (lowercase)
     */
    public function getParserFormats(): array;

    /**
     * List all registered renderer format names
     *
     * @return list<string> Format identifiers (lowercase)
     */
    public function getRendererFormats(): array;

    /**
     * List formats that have both a parser and a renderer
     *
     * These are the formats that support round-tripping or conversion
     * between input and output.
     *
     * @return list<string> Format identifiers (lowercase)
     */
    public function getConvertibleFormats(): array;
}
