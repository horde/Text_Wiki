<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Test\Integration;

use Horde\Text\Wiki\BBCode\BBCodeParser;
use Horde\Text\Wiki\Renderer\Xhtml;
use PHPUnit\Framework\TestCase;

/**
 * Integration test: BBCode → Parser → AST → Xhtml Renderer
 *
 * Tests the complete pipeline with modern typed AST.
 */
class BBCodeIntegrationTest extends TestCase
{
    private BBCodeParser $parser;
    private Xhtml $renderer;

    protected function setUp(): void
    {
        $this->parser = new BBCodeParser();
        $this->renderer = new Xhtml();
    }

    public function testSimpleBold(): void
    {
        $bbcode = '[b]bold text[/b]';
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        $this->assertSame('<strong>bold text</strong>', $html);
    }

    public function testNestedFormatting(): void
    {
        $bbcode = '[b]bold [i]and italic[/i] text[/b]';
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        $this->assertSame('<strong>bold <em>and italic</em> text</strong>', $html);
    }

    public function testUrlWithHref(): void
    {
        $bbcode = '[url=http://example.com]link text[/url]';
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        $this->assertSame('<a href="http://example.com">link text</a>', $html);
    }

    public function testUrlFromContent(): void
    {
        $bbcode = '[url]http://example.com[/url]';
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        $this->assertSame('<a href="http://example.com">http://example.com</a>', $html);
    }

    public function testList(): void
    {
        $bbcode = "[list]\n[*]Item 1\n[*]Item 2\n[/list]";
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        // May have newlines in output
        $html = str_replace("\n", '', $html);
        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('<li>Item 1</li>', $html);
        $this->assertStringContainsString('<li>Item 2</li>', $html);
        $this->assertStringContainsString('</ul>', $html);
    }

    public function testCode(): void
    {
        $bbcode = '[code]echo "hello";[/code]';
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<pre><code>', $html);
        $this->assertStringContainsString('echo', $html);
        $this->assertStringContainsString('</code></pre>', $html);
    }

    public function testXSSPrevention(): void
    {
        $bbcode = '[url=javascript:alert(1)]malicious[/url]';
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        // Should be treated as text, not a link
        $this->assertStringNotContainsString('<a href', $html);
        $this->assertStringContainsString('[url=', $html);
    }

    public function testColorInjectionPrevention(): void
    {
        $bbcode = '[color=red; background: url(evil)]text[/color]';
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        // Should be treated as text
        $this->assertStringNotContainsString('style=', $html);
        $this->assertStringContainsString('[color=', $html);
    }

    public function testAutoCloseUnclosed(): void
    {
        $bbcode = '[b]unclosed bold';
        $doc = $this->parser->parse($bbcode);

        // Should have bold element marked as auto-closed
        $children = $doc->getChildren();
        $this->assertCount(1, $children);
        $this->assertTrue($children[0]->isAutoClosed());

        $html = $this->renderer->render($doc);
        $this->assertSame('<strong>unclosed bold</strong>', $html);
    }

    public function testMultipleTags(): void
    {
        $bbcode = 'Normal [b]bold[/b] [i]italic[/i] [u]underline[/u] text';
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('Normal ', $html);
        $this->assertStringContainsString('<strong>bold</strong>', $html);
        $this->assertStringContainsString('<em>italic</em>', $html);
        $this->assertStringContainsString('<u>underline</u>', $html);
        $this->assertStringContainsString(' text', $html);
    }
}
