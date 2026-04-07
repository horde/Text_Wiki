<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\BBCode;

use Horde\Text\Wiki\BBCode\Tag\BoldTag;
use Horde\Text\Wiki\BBCode\Tag\CenterTag;
use Horde\Text\Wiki\BBCode\Tag\CodeTag;
use Horde\Text\Wiki\BBCode\Tag\ColorTag;
use Horde\Text\Wiki\BBCode\Tag\EmailTag;
use Horde\Text\Wiki\BBCode\Tag\FontTag;
use Horde\Text\Wiki\BBCode\Tag\HrTag;
use Horde\Text\Wiki\BBCode\Tag\ImgTag;
use Horde\Text\Wiki\BBCode\Tag\ItalicTag;
use Horde\Text\Wiki\BBCode\Tag\JustifyTag;
use Horde\Text\Wiki\BBCode\Tag\LeftTag;
use Horde\Text\Wiki\BBCode\Tag\ListItemTag;
use Horde\Text\Wiki\BBCode\Tag\ListTag;
use Horde\Text\Wiki\BBCode\Tag\QuoteTag;
use Horde\Text\Wiki\BBCode\Tag\RightTag;
use Horde\Text\Wiki\BBCode\Tag\SizeTag;
use Horde\Text\Wiki\BBCode\Tag\StrikeTag;
use Horde\Text\Wiki\BBCode\Tag\SubscriptTag;
use Horde\Text\Wiki\BBCode\Tag\SuperscriptTag;
use Horde\Text\Wiki\BBCode\Tag\UnderlineTag;
use Horde\Text\Wiki\BBCode\Tag\UrlTag;
use Horde\Text\Wiki\BBCode\Tag\YoutubeTag;
use Horde\Text\Wiki\GenericStructureBuilder;
use Horde\Text\Wiki\Node\DocumentNode;
use Horde\Text\Wiki\Parser;
use Horde\Text\Wiki\SimpleTagRegistry;

/**
 * BBCode parser
 *
 * Parses BBCode markup into typed AST using:
 * - BBCodeTokenizer (BBCode text → tokens)
 * - GenericStructureBuilder (tokens → validated AST)
 * - TagRegistry (12 core BBCode tags)
 *
 * @author   Bertrand Gugger <bertrand@toggg.com>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class BBCodeParser implements Parser
{
    private BBCodeTokenizer $tokenizer;
    private GenericStructureBuilder $builder;

    /**
     * Constructor
     *
     * Sets up tokenizer, tag registry, and structure builder.
     */
    public function __construct()
    {
        $this->tokenizer = new BBCodeTokenizer();

        // Register core BBCode tags
        $registry = new SimpleTagRegistry();

        // Basic formatting
        $registry->register(new BoldTag());
        $registry->register(new ItalicTag());
        $registry->register(new UnderlineTag());
        $registry->register(new StrikeTag());
        $registry->register(new SuperscriptTag());
        $registry->register(new SubscriptTag());

        // Links and media
        $registry->register(new UrlTag());
        $registry->register(new EmailTag());
        $registry->register(new ImgTag());
        $registry->register(new YoutubeTag());

        // Block elements
        $registry->register(new QuoteTag());
        $registry->register(new CodeTag());
        $registry->register(new HrTag());

        // Alignment
        $registry->register(new CenterTag());
        $registry->register(new LeftTag());
        $registry->register(new RightTag());
        $registry->register(new JustifyTag());

        // Lists
        $registry->register(new ListTag());
        $registry->register(new ListItemTag());

        // Styling
        $registry->register(new ColorTag());
        $registry->register(new FontTag());
        $registry->register(new SizeTag());

        $this->builder = new GenericStructureBuilder($registry);
    }

    /**
     * Parse BBCode text into typed AST
     *
     * @param string $text BBCode input text
     *
     * @return DocumentNode Document root with validated structure
     */
    public function parse(string $text): DocumentNode
    {
        // Tokenize
        $tokens = $this->tokenizer->tokenize($text);

        // Build structure
        return $this->builder->build($tokens);
    }

    /**
     * Get format identifier
     *
     * @return string 'bbcode'
     */
    public function getFormat(): string
    {
        return 'bbcode';
    }
}
