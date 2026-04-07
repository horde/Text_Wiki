<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Yawiki;

use Horde\Text\Wiki\GenericStructureBuilder;
use Horde\Text\Wiki\Node\DocumentNode;
use Horde\Text\Wiki\Parser;
use Horde\Text\Wiki\SimpleTagRegistry;
use Horde\Text\Wiki\Yawiki\Tag\AnchorTag;
use Horde\Text\Wiki\Yawiki\Tag\BlockquoteTag;
use Horde\Text\Wiki\Yawiki\Tag\BoldTag;
use Horde\Text\Wiki\Yawiki\Tag\BreakTag;
use Horde\Text\Wiki\Yawiki\Tag\CellTag;
use Horde\Text\Wiki\Yawiki\Tag\CenterTag;
use Horde\Text\Wiki\Yawiki\Tag\CodeTag;
use Horde\Text\Wiki\Yawiki\Tag\ColortextTag;
use Horde\Text\Wiki\Yawiki\Tag\DefdefTag;
use Horde\Text\Wiki\Yawiki\Tag\DeflistTag;
use Horde\Text\Wiki\Yawiki\Tag\DeftermTag;
use Horde\Text\Wiki\Yawiki\Tag\EmphasisTag;
use Horde\Text\Wiki\Yawiki\Tag\FreelinkTag;
use Horde\Text\Wiki\Yawiki\Tag\HeadingTag;
use Horde\Text\Wiki\Yawiki\Tag\HorizTag;
use Horde\Text\Wiki\Yawiki\Tag\ImageTag;
use Horde\Text\Wiki\Yawiki\Tag\ItalicTag;
use Horde\Text\Wiki\Yawiki\Tag\ListitemTag;
use Horde\Text\Wiki\Yawiki\Tag\ListTag;
use Horde\Text\Wiki\Yawiki\Tag\ParagraphTag;
use Horde\Text\Wiki\Yawiki\Tag\PhplookupTag;
use Horde\Text\Wiki\Yawiki\Tag\ReviseDelTag;
use Horde\Text\Wiki\Yawiki\Tag\ReviseInsTag;
use Horde\Text\Wiki\Yawiki\Tag\RowTag;
use Horde\Text\Wiki\Yawiki\Tag\StrongTag;
use Horde\Text\Wiki\Yawiki\Tag\SubscriptTag;
use Horde\Text\Wiki\Yawiki\Tag\SuperscriptTag;
use Horde\Text\Wiki\Yawiki\Tag\TableTag;
use Horde\Text\Wiki\Yawiki\Tag\TocTag;
use Horde\Text\Wiki\Yawiki\Tag\TtTag;
use Horde\Text\Wiki\Yawiki\Tag\UnderlineTag;
use Horde\Text\Wiki\Yawiki\Tag\UrlTag;

/**
 * Yawiki parser
 *
 * Parses Yawiki markup (formerly "Default" wiki format) into typed AST.
 *
 * Uses:
 * - YawikiTokenizer (wiki text -> tokens)
 * - GenericStructureBuilder (tokens -> validated AST)
 * - TagRegistry (32 Yawiki tags)
 *
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   Justin Patrin <papercrane@reversefold.com>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class YawikiParser implements Parser
{
    private YawikiTokenizer $tokenizer;
    private GenericStructureBuilder $builder;

    /**
     * Constructor
     *
     * Sets up tokenizer, tag registry, and structure builder.
     */
    public function __construct()
    {
        $this->tokenizer = new YawikiTokenizer();

        $registry = new SimpleTagRegistry();

        // Inline formatting
        $registry->register(new BoldTag());
        $registry->register(new ItalicTag());
        $registry->register(new StrongTag());
        $registry->register(new EmphasisTag());
        $registry->register(new UnderlineTag());
        $registry->register(new TtTag());
        $registry->register(new SuperscriptTag());
        $registry->register(new SubscriptTag());
        $registry->register(new ColortextTag());
        $registry->register(new ReviseDelTag());
        $registry->register(new ReviseInsTag());
        $registry->register(new BreakTag());

        // Links
        $registry->register(new UrlTag());
        $registry->register(new FreelinkTag());
        $registry->register(new PhplookupTag());

        // Block elements
        $registry->register(new HeadingTag());
        $registry->register(new HorizTag());
        $registry->register(new CodeTag());
        $registry->register(new BlockquoteTag());
        $registry->register(new CenterTag());
        $registry->register(new ParagraphTag());

        // Self-closing blocks
        $registry->register(new AnchorTag());
        $registry->register(new TocTag());
        $registry->register(new ImageTag());

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
     * Parse Yawiki text into typed AST
     *
     * @param string $text Yawiki input text
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
     * @return string 'yawiki'
     */
    public function getFormat(): string
    {
        return 'yawiki';
    }
}
