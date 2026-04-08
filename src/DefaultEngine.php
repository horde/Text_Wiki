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
 * Default engine — Yawiki parser + catalog-resolved renderers
 *
 * The "Default" dialect is Yawiki (Yet Another Wiki), the original Text_Wiki
 * markup created by Paul M. Jones. This engine uses a FormatCatalog to resolve
 * renderers, making it extensible with custom formats.
 *
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   Justin Patrin <justinpatrin@php.net>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class DefaultEngine implements WikiEngine
{
    private FormatCatalog $catalog;
    private Parser $parser;

    public function __construct(?FormatCatalog $catalog = null)
    {
        $this->catalog = $catalog ?? SimpleFormatCatalog::withDefaults();
        $this->parser = $this->catalog->getParser('yawiki');
    }

    public function transform(string $text, string $format = 'Xhtml'): string
    {
        return $this->catalog->getRenderer($format)->render(
            $this->parser->parse($text)
        );
    }
}
