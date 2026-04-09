<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Cowiki;

use Horde\Text\Wiki\Cowiki\Tag\BlockquoteTag;
use Horde\Text\Wiki\Cowiki\Tag\BoldTag;
use Horde\Text\Wiki\Cowiki\Tag\CellTag;
use Horde\Text\Wiki\Cowiki\Tag\CodeTag;
use Horde\Text\Wiki\Cowiki\Tag\HeadingTag;
use Horde\Text\Wiki\Cowiki\Tag\HorizTag;
use Horde\Text\Wiki\Cowiki\Tag\ItalicTag;
use Horde\Text\Wiki\Cowiki\Tag\ListitemTag;
use Horde\Text\Wiki\Cowiki\Tag\ListTag;
use Horde\Text\Wiki\Cowiki\Tag\ParagraphTag;
use Horde\Text\Wiki\Cowiki\Tag\RawTag;
use Horde\Text\Wiki\Cowiki\Tag\RowTag;
use Horde\Text\Wiki\Cowiki\Tag\SubscriptTag;
use Horde\Text\Wiki\Cowiki\Tag\SuperscriptTag;
use Horde\Text\Wiki\Cowiki\Tag\TableTag;
use Horde\Text\Wiki\Cowiki\Tag\TocTag;
use Horde\Text\Wiki\Cowiki\Tag\TtTag;
use Horde\Text\Wiki\Cowiki\Tag\UnderlineTag;
use Horde\Text\Wiki\Cowiki\Tag\UrlTag;
use Horde\Text\Wiki\Cowiki\Tag\WikilinkTag;
use Horde\Text\Wiki\GenericStructureBuilder;
use Horde\Text\Wiki\Node\DocumentNode;
use Horde\Text\Wiki\Parser;
use Horde\Text\Wiki\SimpleTagRegistry;
use Horde\Text\Wiki\TagRegistry;

/**
 * Cowiki parser
 *
 * Parses Cowiki markup into typed AST using:
 * - CowikiTokenizer (Cowiki text → tokens)
 * - GenericStructureBuilder (tokens → validated AST)
 * - TagRegistry (20 Cowiki tags)
 *
 * @author   Daniel T. Gorski
 * @author   Justin Patrin <papercrane@reversefold.com>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class CowikiParser implements Parser
{
    private CowikiTokenizer $tokenizer;
    private GenericStructureBuilder $builder;

    public function __construct(?TagRegistry $registry = null)
    {
        $this->tokenizer = new CowikiTokenizer();

        $registry ??= $this->createDefaultRegistry();
        $this->builder = new GenericStructureBuilder($registry);
    }

    private function createDefaultRegistry(): TagRegistry
    {
        $registry = new SimpleTagRegistry();

        // Inline formatting
        $registry->register(new BoldTag());
        $registry->register(new ItalicTag());
        $registry->register(new UnderlineTag());
        $registry->register(new TtTag());
        $registry->register(new SuperscriptTag());
        $registry->register(new SubscriptTag());

        // Links
        $registry->register(new UrlTag());
        $registry->register(new WikilinkTag());

        // Block elements
        $registry->register(new HeadingTag());
        $registry->register(new HorizTag());
        $registry->register(new CodeTag());
        $registry->register(new RawTag());
        $registry->register(new BlockquoteTag());
        $registry->register(new TocTag());
        $registry->register(new ParagraphTag());

        // Lists
        $registry->register(new ListTag());
        $registry->register(new ListitemTag());

        // Tables
        $registry->register(new TableTag());
        $registry->register(new RowTag());
        $registry->register(new CellTag());

        return $registry;
    }

    /**
     * Parse Cowiki text into typed AST
     *
     * @param string $text Cowiki input text
     *
     * @return DocumentNode Document root with validated structure
     */
    public function parse(string $text): DocumentNode
    {
        $tokens = $this->tokenizer->tokenize($text);

        return $this->builder->build($tokens);
    }

    /**
     * Get format identifier
     *
     * @return string 'cowiki'
     */
    public function getFormat(): string
    {
        return 'cowiki';
    }
}
