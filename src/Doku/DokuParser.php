<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Doku;

use Horde\Text\Wiki\Doku\Tag\BlockquoteTag;
use Horde\Text\Wiki\Doku\Tag\BoldTag;
use Horde\Text\Wiki\Doku\Tag\BreakTag;
use Horde\Text\Wiki\Doku\Tag\CellTag;
use Horde\Text\Wiki\Doku\Tag\CenterTag;
use Horde\Text\Wiki\Doku\Tag\CodeTag;
use Horde\Text\Wiki\Doku\Tag\DefdefTag;
use Horde\Text\Wiki\Doku\Tag\DeflistTag;
use Horde\Text\Wiki\Doku\Tag\DeftermTag;
use Horde\Text\Wiki\Doku\Tag\DelTag;
use Horde\Text\Wiki\Doku\Tag\HeadingTag;
use Horde\Text\Wiki\Doku\Tag\HorizTag;
use Horde\Text\Wiki\Doku\Tag\ImageTag;
use Horde\Text\Wiki\Doku\Tag\ItalicTag;
use Horde\Text\Wiki\Doku\Tag\ListitemTag;
use Horde\Text\Wiki\Doku\Tag\ListTag;
use Horde\Text\Wiki\Doku\Tag\ParagraphTag;
use Horde\Text\Wiki\Doku\Tag\RawTag;
use Horde\Text\Wiki\Doku\Tag\RowTag;
use Horde\Text\Wiki\Doku\Tag\SubscriptTag;
use Horde\Text\Wiki\Doku\Tag\SuperscriptTag;
use Horde\Text\Wiki\Doku\Tag\TableTag;
use Horde\Text\Wiki\Doku\Tag\TtTag;
use Horde\Text\Wiki\Doku\Tag\UnderlineTag;
use Horde\Text\Wiki\Doku\Tag\UrlTag;
use Horde\Text\Wiki\Doku\Tag\WikilinkTag;
use Horde\Text\Wiki\GenericStructureBuilder;
use Horde\Text\Wiki\Node\DocumentNode;
use Horde\Text\Wiki\Parser;
use Horde\Text\Wiki\SimpleTagRegistry;

/**
 * DokuWiki parser
 *
 * Parses DokuWiki markup into typed AST using:
 * - DokuTokenizer (DokuWiki text → tokens)
 * - GenericStructureBuilder (tokens → validated AST)
 * - TagRegistry (26 DokuWiki tags)
 *
 * Supports DokuWiki core syntax plus Horde extensions.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class DokuParser implements Parser
{
    private DokuTokenizer $tokenizer;
    private GenericStructureBuilder $builder;

    public function __construct()
    {
        $this->tokenizer = new DokuTokenizer();

        $registry = new SimpleTagRegistry();

        // Inline formatting
        $registry->register(new BoldTag());
        $registry->register(new ItalicTag());
        $registry->register(new UnderlineTag());
        $registry->register(new TtTag());
        $registry->register(new SuperscriptTag());
        $registry->register(new SubscriptTag());
        $registry->register(new DelTag());
        $registry->register(new BreakTag());

        // Links
        $registry->register(new UrlTag());
        $registry->register(new WikilinkTag());
        $registry->register(new ImageTag());

        // Block elements
        $registry->register(new HeadingTag());
        $registry->register(new HorizTag());
        $registry->register(new CodeTag());
        $registry->register(new RawTag());
        $registry->register(new BlockquoteTag());
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

        $this->builder = new GenericStructureBuilder($registry);
    }

    /**
     * Parse DokuWiki text into typed AST
     *
     * @param string $text DokuWiki input text
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
     * @return string 'doku'
     */
    public function getFormat(): string
    {
        return 'doku';
    }
}
