<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Mediawiki;

use Horde\Text\Wiki\GenericStructureBuilder;
use Horde\Text\Wiki\Node\DocumentNode;
use Horde\Text\Wiki\Parser;
use Horde\Text\Wiki\SimpleTagRegistry;
use Horde\Text\Wiki\Mediawiki\Tag\AnchorTag;
use Horde\Text\Wiki\Mediawiki\Tag\BlockquoteTag;
use Horde\Text\Wiki\Mediawiki\Tag\BreakTag;
use Horde\Text\Wiki\Mediawiki\Tag\CellTag;
use Horde\Text\Wiki\Mediawiki\Tag\CenterTag;
use Horde\Text\Wiki\Mediawiki\Tag\CodeTag;
use Horde\Text\Wiki\Mediawiki\Tag\ColorTag;
use Horde\Text\Wiki\Mediawiki\Tag\DefdefTag;
use Horde\Text\Wiki\Mediawiki\Tag\DeflistTag;
use Horde\Text\Wiki\Mediawiki\Tag\DeftermTag;
use Horde\Text\Wiki\Mediawiki\Tag\DelTag;
use Horde\Text\Wiki\Mediawiki\Tag\EmailTag;
use Horde\Text\Wiki\Mediawiki\Tag\EmphasisTag;
use Horde\Text\Wiki\Mediawiki\Tag\FontTag;
use Horde\Text\Wiki\Mediawiki\Tag\HeadingTag;
use Horde\Text\Wiki\Mediawiki\Tag\HorizTag;
use Horde\Text\Wiki\Mediawiki\Tag\ImageTag;
use Horde\Text\Wiki\Mediawiki\Tag\InsTag;
use Horde\Text\Wiki\Mediawiki\Tag\JustifyTag;
use Horde\Text\Wiki\Mediawiki\Tag\LeftTag;
use Horde\Text\Wiki\Mediawiki\Tag\ListitemTag;
use Horde\Text\Wiki\Mediawiki\Tag\ListTag;
use Horde\Text\Wiki\Mediawiki\Tag\ParagraphTag;
use Horde\Text\Wiki\Mediawiki\Tag\PreformattedTag;
use Horde\Text\Wiki\Mediawiki\Tag\RawTag;
use Horde\Text\Wiki\Mediawiki\Tag\RightTag;
use Horde\Text\Wiki\Mediawiki\Tag\RowTag;
use Horde\Text\Wiki\Mediawiki\Tag\SizeTag;
use Horde\Text\Wiki\Mediawiki\Tag\StrikeTag;
use Horde\Text\Wiki\Mediawiki\Tag\StrongTag;
use Horde\Text\Wiki\Mediawiki\Tag\SubscriptTag;
use Horde\Text\Wiki\Mediawiki\Tag\SuperscriptTag;
use Horde\Text\Wiki\Mediawiki\Tag\TableTag;
use Horde\Text\Wiki\Mediawiki\Tag\TocTag;
use Horde\Text\Wiki\Mediawiki\Tag\TtTag;
use Horde\Text\Wiki\Mediawiki\Tag\UnderlineTag;
use Horde\Text\Wiki\Mediawiki\Tag\UrlTag;
use Horde\Text\Wiki\Mediawiki\Tag\WikilinkTag;
use Horde\Text\Wiki\Mediawiki\Tag\YoutubeTag;

/**
 * MediaWiki parser
 *
 * Parses MediaWiki wikitext into typed AST using:
 * - MediawikiTokenizer (wikitext → tokens)
 * - GenericStructureBuilder (tokens → validated AST)
 * - TagRegistry (39 MediaWiki tags)
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class MediawikiParser implements Parser
{
    private MediawikiTokenizer $tokenizer;
    private GenericStructureBuilder $builder;

    public function __construct(array $options = [])
    {
        $this->tokenizer = new MediawikiTokenizer($options);

        $registry = new SimpleTagRegistry();

        // Inline formatting (MediaWiki uses strong/emphasis, not bold/italic)
        $registry->register(new StrongTag());
        $registry->register(new EmphasisTag());
        $registry->register(new UnderlineTag());
        $registry->register(new TtTag());
        $registry->register(new SuperscriptTag());
        $registry->register(new SubscriptTag());
        $registry->register(new StrikeTag());
        $registry->register(new DelTag());
        $registry->register(new InsTag());

        // Links and media
        $registry->register(new UrlTag());
        $registry->register(new WikilinkTag());
        $registry->register(new ImageTag());
        $registry->register(new EmailTag());
        $registry->register(new YoutubeTag());

        // Anchors
        $registry->register(new AnchorTag());

        // Styling (span-based)
        $registry->register(new ColorTag());
        $registry->register(new FontTag());
        $registry->register(new SizeTag());

        // Alignment (div-based)
        $registry->register(new CenterTag());
        $registry->register(new LeftTag());
        $registry->register(new RightTag());
        $registry->register(new JustifyTag());

        // Block elements
        $registry->register(new HeadingTag());
        $registry->register(new HorizTag());
        $registry->register(new CodeTag());
        $registry->register(new PreformattedTag());
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

        // Definition lists
        $registry->register(new DeflistTag());
        $registry->register(new DeftermTag());
        $registry->register(new DefdefTag());

        // Structure
        $registry->register(new BreakTag());

        $this->builder = new GenericStructureBuilder($registry);
    }

    public function parse(string $text): DocumentNode
    {
        $tokens = $this->tokenizer->tokenize($text);

        return $this->builder->build($tokens);
    }

    public function getFormat(): string
    {
        return 'mediawiki';
    }
}
