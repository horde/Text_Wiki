<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Test\BBCode\Tag;

use Horde\Text\Wiki\BBCode\Tag\BoldTag;
use Horde\Text\Wiki\BBCode\Tag\CodeTag;
use Horde\Text\Wiki\BBCode\Tag\ColorTag;
use Horde\Text\Wiki\BBCode\Tag\ListItemTag;
use Horde\Text\Wiki\BBCode\Tag\ListTag;
use Horde\Text\Wiki\BBCode\Tag\UrlTag;
use Horde\Text\Wiki\TagType;
use PHPUnit\Framework\TestCase;

class TagDefinitionTest extends TestCase
{
    public function testBoldTagBasics(): void
    {
        $tag = new BoldTag();

        $this->assertSame('b', $tag->getName());
        $this->assertSame(TagType::INLINE, $tag->getType());
        $this->assertTrue($tag->requiresClosing());
        $this->assertTrue($tag->shouldParseContent());
        $this->assertTrue($tag->validateAttributes([]));
    }

    public function testCodeTagIsVerbatim(): void
    {
        $tag = new CodeTag();

        $this->assertSame('code', $tag->getName());
        $this->assertSame(TagType::BLOCK, $tag->getType());
        $this->assertFalse($tag->shouldParseContent());
    }

    public function testListItemIsSelfClosing(): void
    {
        $tag = new ListItemTag();

        $this->assertSame('*', $tag->getName());
        // [*] requires closing to accept children (implicitly closed by next [*] or [/list])
        $this->assertTrue($tag->requiresClosing());
    }

    public function testUrlTagValidation(): void
    {
        $tag = new UrlTag();

        // No href attribute is valid (href comes from content)
        $this->assertTrue($tag->validateAttributes([]));

        // Valid href
        $this->assertTrue($tag->validateAttributes(['href' => 'http://example.com']));

        // Invalid href (javascript:)
        $this->assertFalse($tag->validateAttributes(['href' => 'javascript:alert(1)']));
    }

    public function testColorTagValidation(): void
    {
        $tag = new ColorTag();

        // Missing color attribute
        $this->assertFalse($tag->validateAttributes([]));

        // Valid hex color
        $this->assertTrue($tag->validateAttributes(['color' => '#FF0000']));

        // Valid named color
        $this->assertTrue($tag->validateAttributes(['color' => 'red']));

        // Invalid color (CSS injection)
        $this->assertFalse($tag->validateAttributes(['color' => 'red; background: url(evil)']));
    }

    public function testListNestingRules(): void
    {
        $listTag = new ListTag();
        $listItemTag = new ListItemTag();
        $boldTag = new BoldTag();

        // [list] can contain [*]
        $this->assertTrue($listTag->canContain($listItemTag));

        // [list] can contain nested [list]
        $this->assertTrue($listTag->canContain($listTag));

        // [list] cannot contain [b]
        $this->assertFalse($listTag->canContain($boldTag));
    }

    public function testBlockCannotContainBlock(): void
    {
        $codeTag = new CodeTag();

        // Block tags can't contain other block tags by default
        $this->assertFalse($codeTag->canContain($codeTag));
    }
}
