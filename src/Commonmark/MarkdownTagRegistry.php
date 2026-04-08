<?php

declare(strict_types=1);

/**
 * Copyright 2025-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Commonmark;

use Horde\Text\Wiki\Commonmark\Tag\BlockquoteTag;
use Horde\Text\Wiki\Commonmark\Tag\BoldTag;
use Horde\Text\Wiki\Commonmark\Tag\BreakTag;
use Horde\Text\Wiki\Commonmark\Tag\CellTag;
use Horde\Text\Wiki\Commonmark\Tag\CodespanTag;
use Horde\Text\Wiki\Commonmark\Tag\CodeTag;
use Horde\Text\Wiki\Commonmark\Tag\HeadingTag;
use Horde\Text\Wiki\Commonmark\Tag\HorizTag;
use Horde\Text\Wiki\Commonmark\Tag\HtmlblockTag;
use Horde\Text\Wiki\Commonmark\Tag\HtmlinlineTag;
use Horde\Text\Wiki\Commonmark\Tag\ImageTag;
use Horde\Text\Wiki\Commonmark\Tag\ItalicTag;
use Horde\Text\Wiki\Commonmark\Tag\ListitemTag;
use Horde\Text\Wiki\Commonmark\Tag\ListTag;
use Horde\Text\Wiki\Commonmark\Tag\ParagraphTag;
use Horde\Text\Wiki\Commonmark\Tag\RowTag;
use Horde\Text\Wiki\Commonmark\Tag\SoftbreakTag;
use Horde\Text\Wiki\Commonmark\Tag\StrikeTag;
use Horde\Text\Wiki\Commonmark\Tag\TableTag;
use Horde\Text\Wiki\Commonmark\Tag\UrlTag;
use Horde\Text\Wiki\SimpleTagRegistry;

/**
 * Tag registry factories for CommonMark and GFM modes
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class MarkdownTagRegistry
{
    /**
     * Create registry with 16 core CommonMark tags
     */
    public static function commonmark(): SimpleTagRegistry
    {
        $registry = new SimpleTagRegistry();

        // Block elements
        $registry->register(new HeadingTag());
        $registry->register(new ParagraphTag());
        $registry->register(new CodeTag());
        $registry->register(new BlockquoteTag());
        $registry->register(new ListTag());
        $registry->register(new ListitemTag());
        $registry->register(new HorizTag());
        $registry->register(new HtmlblockTag());

        // Inline elements
        $registry->register(new BoldTag());
        $registry->register(new ItalicTag());
        $registry->register(new CodespanTag());
        $registry->register(new UrlTag());
        $registry->register(new ImageTag());
        $registry->register(new HtmlinlineTag());
        $registry->register(new BreakTag());
        $registry->register(new SoftbreakTag());

        return $registry;
    }

    /**
     * Create registry with 20 tags: CommonMark core + GFM extensions
     */
    public static function gfm(): SimpleTagRegistry
    {
        $registry = self::commonmark();

        // GFM extensions
        $registry->register(new TableTag());
        $registry->register(new RowTag());
        $registry->register(new CellTag());
        $registry->register(new StrikeTag());

        return $registry;
    }
}
