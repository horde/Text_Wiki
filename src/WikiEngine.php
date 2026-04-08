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
 * Wiki engine interface: markup text in, formatted output out
 *
 * Engines combine a parser and renderer to transform wiki markup
 * into a target format in a single call.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
interface WikiEngine
{
    /**
     * Transform wiki markup to an output format
     *
     * @param string $text Source wiki markup
     * @param string $format Target format (Xhtml, Plain, Latex, Docbook, etc.)
     *
     * @return string Rendered output
     */
    public function transform(string $text, string $format = 'Xhtml'): string;
}
