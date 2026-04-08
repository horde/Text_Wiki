<?php

declare(strict_types=1);

/**
 * Copyright 2025-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\FormatCatalog;
use Horde\Text\Wiki\GenericTextWikiException;
use Horde\Text\Wiki\Node\DocumentNode;
use Horde\Text\Wiki\Node\ElementNode;
use Horde\Text\Wiki\Node\TextNode;
use Horde\Text\Wiki\Parser;
use Horde\Text\Wiki\Renderer;
use Horde\Text\Wiki\Renderer\Xhtml;
use Horde\Text\Wiki\SimpleFormatCatalog;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SimpleFormatCatalog::class)]
class SimpleFormatCatalogTest extends TestCase
{
    // ---------------------------------------------------------------
    // Basic registration and lookup
    // ---------------------------------------------------------------

    public function testRegisterAndGetParser(): void
    {
        $catalog = new SimpleFormatCatalog();
        $parser = $this->createMock(Parser::class);
        $parser->method('getFormat')->willReturn('test');

        $catalog->registerParser($parser);

        $this->assertSame($parser, $catalog->getParser('test'));
    }

    public function testRegisterAndGetRenderer(): void
    {
        $catalog = new SimpleFormatCatalog();
        $renderer = $this->createMock(Renderer::class);
        $renderer->method('getFormat')->willReturn('test');

        $catalog->registerRenderer($renderer);

        $this->assertSame($renderer, $catalog->getRenderer('test'));
    }

    // ---------------------------------------------------------------
    // Case insensitivity
    // ---------------------------------------------------------------

    public function testParserLookupIsCaseInsensitive(): void
    {
        $catalog = new SimpleFormatCatalog();
        $parser = $this->createMock(Parser::class);
        $parser->method('getFormat')->willReturn('Yawiki');

        $catalog->registerParser($parser);

        $this->assertSame($parser, $catalog->getParser('yawiki'));
        $this->assertSame($parser, $catalog->getParser('YAWIKI'));
    }

    public function testRendererLookupIsCaseInsensitive(): void
    {
        $catalog = new SimpleFormatCatalog();
        $renderer = $this->createMock(Renderer::class);
        $renderer->method('getFormat')->willReturn('Xhtml');

        $catalog->registerRenderer($renderer);

        $this->assertSame($renderer, $catalog->getRenderer('xhtml'));
        $this->assertSame($renderer, $catalog->getRenderer('XHTML'));
    }

    // ---------------------------------------------------------------
    // Has methods
    // ---------------------------------------------------------------

    public function testHasParserReturnsTrueWhenRegistered(): void
    {
        $catalog = new SimpleFormatCatalog();
        $parser = $this->createMock(Parser::class);
        $parser->method('getFormat')->willReturn('test');

        $catalog->registerParser($parser);

        $this->assertTrue($catalog->hasParser('test'));
        $this->assertTrue($catalog->hasParser('Test'));
    }

    public function testHasParserReturnsFalseWhenNotRegistered(): void
    {
        $catalog = new SimpleFormatCatalog();

        $this->assertFalse($catalog->hasParser('nonexistent'));
    }

    public function testHasRendererReturnsTrueWhenRegistered(): void
    {
        $catalog = new SimpleFormatCatalog();
        $renderer = $this->createMock(Renderer::class);
        $renderer->method('getFormat')->willReturn('test');

        $catalog->registerRenderer($renderer);

        $this->assertTrue($catalog->hasRenderer('test'));
    }

    public function testHasRendererReturnsFalseWhenNotRegistered(): void
    {
        $catalog = new SimpleFormatCatalog();

        $this->assertFalse($catalog->hasRenderer('nonexistent'));
    }

    // ---------------------------------------------------------------
    // Unknown format exceptions
    // ---------------------------------------------------------------

    public function testGetParserThrowsOnUnknownFormat(): void
    {
        $catalog = new SimpleFormatCatalog();

        $this->expectException(GenericTextWikiException::class);
        $catalog->getParser('nonexistent');
    }

    public function testGetRendererThrowsOnUnknownFormat(): void
    {
        $catalog = new SimpleFormatCatalog();

        $this->expectException(GenericTextWikiException::class);
        $catalog->getRenderer('nonexistent');
    }

    // ---------------------------------------------------------------
    // Duplicate registration (replace silently)
    // ---------------------------------------------------------------

    public function testRegisterParserReplacesDuplicate(): void
    {
        $catalog = new SimpleFormatCatalog();
        $parser1 = $this->createMock(Parser::class);
        $parser1->method('getFormat')->willReturn('test');
        $parser2 = $this->createMock(Parser::class);
        $parser2->method('getFormat')->willReturn('test');

        $catalog->registerParser($parser1);
        $catalog->registerParser($parser2);

        $this->assertSame($parser2, $catalog->getParser('test'));
    }

    public function testRegisterRendererReplacesDuplicate(): void
    {
        $catalog = new SimpleFormatCatalog();
        $renderer1 = $this->createMock(Renderer::class);
        $renderer1->method('getFormat')->willReturn('test');
        $renderer2 = $this->createMock(Renderer::class);
        $renderer2->method('getFormat')->willReturn('test');

        $catalog->registerRenderer($renderer1);
        $catalog->registerRenderer($renderer2);

        $this->assertSame($renderer2, $catalog->getRenderer('test'));
    }

    // ---------------------------------------------------------------
    // Format listing
    // ---------------------------------------------------------------

    public function testGetParserFormats(): void
    {
        $catalog = new SimpleFormatCatalog();
        $parser1 = $this->createMock(Parser::class);
        $parser1->method('getFormat')->willReturn('alpha');
        $parser2 = $this->createMock(Parser::class);
        $parser2->method('getFormat')->willReturn('beta');

        $catalog->registerParser($parser1);
        $catalog->registerParser($parser2);

        $formats = $catalog->getParserFormats();
        $this->assertCount(2, $formats);
        $this->assertContains('alpha', $formats);
        $this->assertContains('beta', $formats);
    }

    public function testGetRendererFormats(): void
    {
        $catalog = new SimpleFormatCatalog();
        $renderer1 = $this->createMock(Renderer::class);
        $renderer1->method('getFormat')->willReturn('alpha');
        $renderer2 = $this->createMock(Renderer::class);
        $renderer2->method('getFormat')->willReturn('beta');

        $catalog->registerRenderer($renderer1);
        $catalog->registerRenderer($renderer2);

        $formats = $catalog->getRendererFormats();
        $this->assertCount(2, $formats);
        $this->assertContains('alpha', $formats);
        $this->assertContains('beta', $formats);
    }

    public function testGetConvertibleFormats(): void
    {
        $catalog = new SimpleFormatCatalog();

        $parser = $this->createMock(Parser::class);
        $parser->method('getFormat')->willReturn('shared');
        $catalog->registerParser($parser);

        $parserOnly = $this->createMock(Parser::class);
        $parserOnly->method('getFormat')->willReturn('parseonly');
        $catalog->registerParser($parserOnly);

        $renderer = $this->createMock(Renderer::class);
        $renderer->method('getFormat')->willReturn('shared');
        $catalog->registerRenderer($renderer);

        $rendererOnly = $this->createMock(Renderer::class);
        $rendererOnly->method('getFormat')->willReturn('renderonly');
        $catalog->registerRenderer($rendererOnly);

        $convertible = $catalog->getConvertibleFormats();
        $this->assertCount(1, $convertible);
        $this->assertContains('shared', $convertible);
    }

    // ---------------------------------------------------------------
    // withDefaults()
    // ---------------------------------------------------------------

    public function testWithDefaultsHasAllParsers(): void
    {
        $catalog = SimpleFormatCatalog::withDefaults();

        $expected = ['bbcode', 'cowiki', 'creole', 'doku', 'mediawiki', 'tiki', 'yawiki'];
        $formats = $catalog->getParserFormats();
        sort($formats);

        $this->assertSame($expected, $formats);
    }

    public function testWithDefaultsHasAllRenderers(): void
    {
        $catalog = SimpleFormatCatalog::withDefaults();

        $expected = [
            'bbcode', 'cowiki', 'creole', 'docbook', 'doku',
            'latex', 'mediawiki', 'plain', 'tiki', 'xhtml', 'yawiki',
        ];
        $formats = $catalog->getRendererFormats();
        sort($formats);

        $this->assertSame($expected, $formats);
    }

    public function testWithDefaultsConvertibleFormats(): void
    {
        $catalog = SimpleFormatCatalog::withDefaults();

        $convertible = $catalog->getConvertibleFormats();
        sort($convertible);

        // 7 formats have both parser and renderer
        $this->assertSame(
            ['bbcode', 'cowiki', 'creole', 'doku', 'mediawiki', 'tiki', 'yawiki'],
            $convertible
        );
    }

    public function testWithDefaultsImplementsFormatCatalog(): void
    {
        $catalog = SimpleFormatCatalog::withDefaults();

        $this->assertInstanceOf(FormatCatalog::class, $catalog);
    }

    // ---------------------------------------------------------------
    // Element handler injection on renderer
    // ---------------------------------------------------------------

    public function testRendererElementHandlerOverridesBuiltIn(): void
    {
        $renderer = new Xhtml();
        $renderer->setElementHandler('bold', function (ElementNode $node, $visitor): string {
            return '<b class="custom">' . $visitor->renderChildren($node) . '</b>';
        });

        $doc = new DocumentNode();
        $bold = new ElementNode('bold');
        $bold->addChild(new TextNode('test'));
        $doc->addChild($bold);

        $result = $renderer->render($doc);
        $this->assertStringContainsString('<b class="custom">test</b>', $result);
        $this->assertStringNotContainsString('<strong>', $result);
    }

    public function testRendererElementHandlerForCustomTag(): void
    {
        $renderer = new Xhtml();
        $renderer->setElementHandler('widget', function (ElementNode $node, $visitor): string {
            return '<div class="widget">' . $visitor->renderChildren($node) . '</div>';
        });

        $doc = new DocumentNode();
        $widget = new ElementNode('widget');
        $widget->addChild(new TextNode('content'));
        $doc->addChild($widget);

        $result = $renderer->render($doc);
        $this->assertStringContainsString('<div class="widget">content</div>', $result);
    }

    public function testElementHandlerIsCaseInsensitive(): void
    {
        $renderer = new Xhtml();
        $renderer->setElementHandler('BOLD', function (ElementNode $node, $visitor): string {
            return '<b>custom</b>';
        });

        $doc = new DocumentNode();
        $bold = new ElementNode('bold');
        $bold->addChild(new TextNode('test'));
        $doc->addChild($bold);

        $result = $renderer->render($doc);
        $this->assertStringContainsString('<b>custom</b>', $result);
    }
}
