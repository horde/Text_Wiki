<?php

declare(strict_types=1);

/**
 * Copyright 2025-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki;

use Horde\Text\Wiki\BBCode\BBCodeParser;
use Horde\Text\Wiki\Cowiki\CowikiParser;
use Horde\Text\Wiki\Creole\CreoleParser;
use Horde\Text\Wiki\Doku\DokuParser;
use Horde\Text\Wiki\Mediawiki\MediawikiParser;
use Horde\Text\Wiki\Renderer\BBCode;
use Horde\Text\Wiki\Renderer\Cowiki;
use Horde\Text\Wiki\Renderer\Creole;
use Horde\Text\Wiki\Renderer\Docbook;
use Horde\Text\Wiki\Renderer\Doku;
use Horde\Text\Wiki\Renderer\Latex;
use Horde\Text\Wiki\Renderer\Mediawiki;
use Horde\Text\Wiki\Renderer\Plain;
use Horde\Text\Wiki\Renderer\Tiki;
use Horde\Text\Wiki\Renderer\Xhtml;
use Horde\Text\Wiki\Renderer\Yawiki;
use Horde\Text\Wiki\Tiki\TikiParser;
use Horde\Text\Wiki\Yawiki\YawikiParser;

/**
 * Simple format catalog implementation
 *
 * Stores and retrieves parsers and renderers by format name.
 * Use withDefaults() for a catalog pre-loaded with all built-in formats.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class SimpleFormatCatalog implements FormatCatalog
{
    /** @var array<string, Parser> */
    private array $parsers = [];

    /** @var array<string, Renderer> */
    private array $renderers = [];

    /**
     * Create a catalog pre-loaded with all built-in parsers and renderers
     */
    public static function withDefaults(): self
    {
        $catalog = new self();

        $catalog->registerParser(new BBCodeParser());
        $catalog->registerParser(new CowikiParser());
        $catalog->registerParser(new CreoleParser());
        $catalog->registerParser(new DokuParser());
        $catalog->registerParser(new MediawikiParser());
        $catalog->registerParser(new TikiParser());
        $catalog->registerParser(new YawikiParser());

        $catalog->registerRenderer(new BBCode());
        $catalog->registerRenderer(new Cowiki());
        $catalog->registerRenderer(new Creole());
        $catalog->registerRenderer(new Docbook());
        $catalog->registerRenderer(new Doku());
        $catalog->registerRenderer(new Latex());
        $catalog->registerRenderer(new Mediawiki());
        $catalog->registerRenderer(new Plain());
        $catalog->registerRenderer(new Tiki());
        $catalog->registerRenderer(new Xhtml());
        $catalog->registerRenderer(new Yawiki());

        return $catalog;
    }

    public function registerParser(Parser $parser): void
    {
        $this->parsers[strtolower($parser->getFormat())] = $parser;
    }

    public function registerRenderer(Renderer $renderer): void
    {
        $this->renderers[strtolower($renderer->getFormat())] = $renderer;
    }

    public function getParser(string $format): Parser
    {
        $key = strtolower($format);

        return $this->parsers[$key]
            ?? throw new GenericTextWikiException("Unknown parser format '$format'");
    }

    public function getRenderer(string $format): Renderer
    {
        $key = strtolower($format);

        return $this->renderers[$key]
            ?? throw new GenericTextWikiException("Unknown renderer format '$format'");
    }

    public function hasParser(string $format): bool
    {
        return isset($this->parsers[strtolower($format)]);
    }

    public function hasRenderer(string $format): bool
    {
        return isset($this->renderers[strtolower($format)]);
    }

    public function getParserFormats(): array
    {
        return array_values(array_keys($this->parsers));
    }

    public function getRendererFormats(): array
    {
        return array_values(array_keys($this->renderers));
    }

    public function getConvertibleFormats(): array
    {
        return array_values(
            array_intersect(
                array_keys($this->parsers),
                array_keys($this->renderers),
            )
        );
    }
}
