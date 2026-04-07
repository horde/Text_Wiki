<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Test\Renderer;

use Horde\Text\Wiki\Node\DocumentNode;
use Horde\Text\Wiki\Node\ElementNode;
use Horde\Text\Wiki\Node\TextNode;
use Horde\Text\Wiki\Renderer\Xhtml;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Xhtml::class)]
class XhtmlTest extends TestCase
{
    private Xhtml $renderer;

    protected function setUp(): void
    {
        $this->renderer = new Xhtml();
    }

    public function testRenderBold(): void
    {
        $doc = new DocumentNode();
        $bold = new ElementNode('bold');
        $bold->addChild(new TextNode('bold text'));
        $doc->addChild($bold);

        $html = $this->renderer->render($doc);

        $this->assertSame('<strong>bold text</strong>', $html);
    }

    public function testRenderItalic(): void
    {
        $doc = new DocumentNode();
        $italic = new ElementNode('italic');
        $italic->addChild(new TextNode('italic text'));
        $doc->addChild($italic);

        $html = $this->renderer->render($doc);

        $this->assertSame('<em>italic text</em>', $html);
    }

    public function testTextEscaping(): void
    {
        $doc = new DocumentNode();
        $doc->addChild(new TextNode('<script>alert("xss")</script>'));

        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function testRenderUrl(): void
    {
        $doc = new DocumentNode();
        $url = new ElementNode('url', ['href' => 'http://example.com']);
        $url->addChild(new TextNode('link text'));
        $doc->addChild($url);

        $html = $this->renderer->render($doc);

        $this->assertSame('<a href="http://example.com">link text</a>', $html);
    }

    public function testRenderUrlFromContent(): void
    {
        $doc = new DocumentNode();
        $url = new ElementNode('url'); // No href attribute
        $url->addChild(new TextNode('http://example.com'));
        $doc->addChild($url);

        $html = $this->renderer->render($doc);

        $this->assertSame('<a href="http://example.com">http://example.com</a>', $html);
    }

    public function testRenderList(): void
    {
        $doc = new DocumentNode();
        $list = new ElementNode('list');
        $item1 = new ElementNode('listitem');
        $item1->addChild(new TextNode('Item 1'));
        $item2 = new ElementNode('listitem');
        $item2->addChild(new TextNode('Item 2'));
        $list->addChild($item1);
        $list->addChild($item2);
        $doc->addChild($list);

        $html = $this->renderer->render($doc);

        $this->assertSame('<ul><li>Item 1</li><li>Item 2</li></ul>', $html);
    }

    public function testRenderCode(): void
    {
        $doc = new DocumentNode();
        $code = new ElementNode('code');
        $code->addChild(new TextNode('echo "hello";'));
        $doc->addChild($code);

        $html = $this->renderer->render($doc);

        $this->assertSame('<pre><code>echo &quot;hello&quot;;</code></pre>', $html);
    }

    public function testRenderColor(): void
    {
        $doc = new DocumentNode();
        $color = new ElementNode('color', ['color' => '#FF0000']);
        $color->addChild(new TextNode('red text'));
        $doc->addChild($color);

        $html = $this->renderer->render($doc);

        $this->assertSame('<span style="color: #FF0000">red text</span>', $html);
    }

    public function testNestedTags(): void
    {
        $doc = new DocumentNode();
        $bold = new ElementNode('bold');
        $italic = new ElementNode('italic');
        $italic->addChild(new TextNode('nested'));
        $bold->addChild($italic);
        $doc->addChild($bold);

        $html = $this->renderer->render($doc);

        $this->assertSame('<strong><em>nested</em></strong>', $html);
    }

    public function testRenderListitemMethodName(): void
    {
        // Test that renderListitem() method works
        $doc = new DocumentNode();
        $item = new ElementNode('listitem');
        $item->addChild(new TextNode('item'));
        $doc->addChild($item);

        $html = $this->renderer->render($doc);

        $this->assertSame('<li>item</li>', $html);
    }
}
