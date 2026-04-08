<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Tiki;

use Horde\Text\Wiki\GenericStructureBuilder;
use Horde\Text\Wiki\Node\DocumentNode;
use Horde\Text\Wiki\Parser;
use Horde\Text\Wiki\SimpleTagRegistry;
use Horde\Text\Wiki\TagRegistry;
use Horde\Text\Wiki\Tiki\Tag\AnchorTag;
use Horde\Text\Wiki\Tiki\Tag\BlockquoteTag;
use Horde\Text\Wiki\Tiki\Tag\BoldTag;
use Horde\Text\Wiki\Tiki\Tag\BreakTag;
use Horde\Text\Wiki\Tiki\Tag\CellTag;
use Horde\Text\Wiki\Tiki\Tag\CenterTag;
use Horde\Text\Wiki\Tiki\Tag\CodeTag;
use Horde\Text\Wiki\Tiki\Tag\ColortextTag;
use Horde\Text\Wiki\Tiki\Tag\DefdefTag;
use Horde\Text\Wiki\Tiki\Tag\DeflistTag;
use Horde\Text\Wiki\Tiki\Tag\DeftermTag;
use Horde\Text\Wiki\Tiki\Tag\HeadingTag;
use Horde\Text\Wiki\Tiki\Tag\HorizTag;
use Horde\Text\Wiki\Tiki\Tag\ImageTag;
use Horde\Text\Wiki\Tiki\Tag\ItalicTag;
use Horde\Text\Wiki\Tiki\Tag\ListitemTag;
use Horde\Text\Wiki\Tiki\Tag\ListTag;
use Horde\Text\Wiki\Tiki\Tag\ParagraphTag;
use Horde\Text\Wiki\Tiki\Tag\RawTag;
use Horde\Text\Wiki\Tiki\Tag\RowTag;
use Horde\Text\Wiki\Tiki\Tag\SubscriptTag;
use Horde\Text\Wiki\Tiki\Tag\SuperscriptTag;
use Horde\Text\Wiki\Tiki\Tag\TableTag;
use Horde\Text\Wiki\Tiki\Tag\TocTag;
use Horde\Text\Wiki\Tiki\Tag\TtTag;
use Horde\Text\Wiki\Tiki\Tag\UnderlineTag;
use Horde\Text\Wiki\Tiki\Tag\UrlTag;
use Horde\Text\Wiki\Tiki\Tag\WikilinkTag;

/**
 * Tiki parser
 *
 * Parses Tiki wiki markup into typed AST using:
 * - TikiTokenizer (Tiki text -> tokens)
 * - GenericStructureBuilder (tokens -> validated AST)
 * - TagRegistry (28 Tiki tags)
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class TikiParser implements Parser
{
    private TikiTokenizer $tokenizer;
    private GenericStructureBuilder $builder;

    public function __construct(?TagRegistry $registry = null)
    {
        $this->tokenizer = new TikiTokenizer();

        $registry = $registry ?? $this->createDefaultRegistry();
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
        $registry->register(new ColortextTag());

        // Links and media
        $registry->register(new UrlTag());
        $registry->register(new WikilinkTag());
        $registry->register(new ImageTag());

        // Block elements
        $registry->register(new HeadingTag());
        $registry->register(new HorizTag());
        $registry->register(new CodeTag());
        $registry->register(new RawTag());
        $registry->register(new BlockquoteTag());
        $registry->register(new TocTag());
        $registry->register(new CenterTag());
        $registry->register(new ParagraphTag());

        // Lists
        $registry->register(new ListTag());
        $registry->register(new ListitemTag());

        // Tables
        $registry->register(new TableTag());
        $registry->register(new RowTag());
        $registry->register(new CellTag());

        // Definition lists
        $registry->register(new DeflistTag());
        $registry->register(new DeftermTag());
        $registry->register(new DefdefTag());

        // Structure
        $registry->register(new AnchorTag());
        $registry->register(new BreakTag());

        return $registry;
    }

    /**
     * Parse Tiki text into typed AST
     *
     * @param string $text Tiki input text
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
     * @return string 'tiki'
     */
    public function getFormat(): string
    {
        return 'tiki';
    }
}
