<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Creole;

use Horde\Text\Wiki\Creole\Tag\BlockquoteTag;
use Horde\Text\Wiki\Creole\Tag\BoldTag;
use Horde\Text\Wiki\Creole\Tag\BreakTag;
use Horde\Text\Wiki\Creole\Tag\CellTag;
use Horde\Text\Wiki\Creole\Tag\CenterTag;
use Horde\Text\Wiki\Creole\Tag\CodeTag;
use Horde\Text\Wiki\Creole\Tag\DefdefTag;
use Horde\Text\Wiki\Creole\Tag\DeflistTag;
use Horde\Text\Wiki\Creole\Tag\DeftermTag;
use Horde\Text\Wiki\Creole\Tag\HeadingTag;
use Horde\Text\Wiki\Creole\Tag\HorizTag;
use Horde\Text\Wiki\Creole\Tag\ImageTag;
use Horde\Text\Wiki\Creole\Tag\ItalicTag;
use Horde\Text\Wiki\Creole\Tag\ListitemTag;
use Horde\Text\Wiki\Creole\Tag\ListTag;
use Horde\Text\Wiki\Creole\Tag\ParagraphTag;
use Horde\Text\Wiki\Creole\Tag\RawTag;
use Horde\Text\Wiki\Creole\Tag\RowTag;
use Horde\Text\Wiki\Creole\Tag\SubscriptTag;
use Horde\Text\Wiki\Creole\Tag\SuperscriptTag;
use Horde\Text\Wiki\Creole\Tag\TableTag;
use Horde\Text\Wiki\Creole\Tag\TtTag;
use Horde\Text\Wiki\Creole\Tag\UnderlineTag;
use Horde\Text\Wiki\Creole\Tag\UrlTag;
use Horde\Text\Wiki\Creole\Tag\WikilinkTag;
use Horde\Text\Wiki\GenericStructureBuilder;
use Horde\Text\Wiki\Node\DocumentNode;
use Horde\Text\Wiki\Parser;
use Horde\Text\Wiki\SimpleTagRegistry;

/**
 * Creole parser
 *
 * Parses Creole markup into typed AST using:
 * - CreoleTokenizer (Creole text → tokens)
 * - GenericStructureBuilder (tokens → validated AST)
 * - TagRegistry (25 Creole tags)
 *
 * Supports Creole 1.0 core syntax plus Horde extensions
 * (underline, superscript, subscript, blockquote, center, deflist).
 *
 * @author   Michele Tomaiuolo <tomamic@yahoo.it>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class CreoleParser implements Parser
{
    private CreoleTokenizer $tokenizer;
    private GenericStructureBuilder $builder;

    public function __construct()
    {
        $this->tokenizer = new CreoleTokenizer();

        $registry = new SimpleTagRegistry();

        // Inline formatting
        $registry->register(new BoldTag());
        $registry->register(new ItalicTag());
        $registry->register(new UnderlineTag());
        $registry->register(new TtTag());
        $registry->register(new SuperscriptTag());
        $registry->register(new SubscriptTag());
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
     * Parse Creole text into typed AST
     *
     * @param string $text Creole input text
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
     * @return string 'creole'
     */
    public function getFormat(): string
    {
        return 'creole';
    }
}
