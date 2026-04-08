<?php

declare(strict_types=1);

/**
 * Copyright 2025-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\DefaultEngine;
use Horde\Text\Wiki\GenericTextWikiException;
use Horde\Text\Wiki\Node\DocumentNode;
use Horde\Text\Wiki\Node\TextNode;
use Horde\Text\Wiki\Parser;
use Horde\Text\Wiki\Renderer;
use Horde\Text\Wiki\SimpleFormatCatalog;
use Horde\Text\Wiki\WikiEngine;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
class DefaultEngineTest extends TestCase
{
    private DefaultEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new DefaultEngine();
    }

    public function testImplementsWikiEngine(): void
    {
        $this->assertInstanceOf(WikiEngine::class, $this->engine);
    }

    public function testTransformToXhtml(): void
    {
        $result = $this->engine->transform("'''bold text'''", 'Xhtml');

        $this->assertStringContainsString('<strong>', $result);
        $this->assertStringContainsString('bold text', $result);
    }

    public function testTransformToPlain(): void
    {
        $result = $this->engine->transform("'''bold text'''", 'Plain');

        $this->assertStringContainsString('bold text', $result);
    }

    public function testTransformToLatex(): void
    {
        $result = $this->engine->transform("'''bold text'''", 'Latex');

        $this->assertStringContainsString('\\textbf{bold text}', $result);
    }

    public function testTransformToDocbook(): void
    {
        $result = $this->engine->transform("'''bold text'''", 'Docbook');

        $this->assertStringContainsString('<emphasis role="bold">bold text</emphasis>', $result);
    }

    public function testTransformToMediawiki(): void
    {
        $result = $this->engine->transform("'''bold text'''", 'Mediawiki');

        $this->assertStringContainsString("'''bold text'''", $result);
    }

    public function testTransformToYawiki(): void
    {
        $result = $this->engine->transform("'''bold text'''", 'Yawiki');

        $this->assertStringContainsString("'''bold text'''", $result);
    }

    public function testTransformComplexDocument(): void
    {
        $source = <<<'WIKI'
++ Main Heading

Some paragraph with '''bold''' text.

* Item 1
* Item 2

----
WIKI;

        $result = $this->engine->transform($source, 'Xhtml');
        $this->assertStringContainsString('<h2', $result);
        $this->assertStringContainsString('<strong>bold</strong>', $result);
        $this->assertStringContainsString('<ul', $result);
        $this->assertStringContainsString('<hr', $result);
    }

    public function testThrowsOnUnknownFormat(): void
    {
        $this->expectException(GenericTextWikiException::class);
        $this->engine->transform('hello', 'UnknownFormat');
    }

    public function testAcceptsCustomCatalog(): void
    {
        $doc = new DocumentNode();
        $doc->addChild(new TextNode('hello'));

        $parser = $this->createMock(Parser::class);
        $parser->method('getFormat')->willReturn('yawiki');
        $parser->method('parse')->willReturn($doc);

        $renderer = $this->createMock(Renderer::class);
        $renderer->method('getFormat')->willReturn('custom');
        $renderer->method('render')->willReturn('CUSTOM:hello');

        $catalog = new SimpleFormatCatalog();
        $catalog->registerParser($parser);
        $catalog->registerRenderer($renderer);

        $engine = new DefaultEngine($catalog);
        $result = $engine->transform('hello', 'custom');

        $this->assertSame('CUSTOM:hello', $result);
    }
}
