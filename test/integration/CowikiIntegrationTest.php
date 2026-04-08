<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Test\Integration;

use Horde\Text\Wiki\Cowiki\CowikiParser;
use Horde\Text\Wiki\Renderer\Xhtml;
use PHPUnit\Framework\TestCase;

/**
 * Integration test: Cowiki markup → Parser → AST → Xhtml Renderer
 *
 * Tests the complete pipeline with modern typed AST.
 * @coversNothing
 */
class CowikiIntegrationTest extends TestCase
{
    private CowikiParser $parser;
    private Xhtml $renderer;

    protected function setUp(): void
    {
        $this->parser = new CowikiParser();
        $this->renderer = new Xhtml();
    }

    // ---------------------------------------------------------------
    // Inline formatting
    // ---------------------------------------------------------------

    public function testBold(): void
    {
        $doc = $this->parser->parse('This is *bold* text');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<strong>bold</strong>', $html);
    }

    public function testItalic(): void
    {
        $doc = $this->parser->parse('This is /italic/ text');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<em>italic</em>', $html);
    }

    public function testUnderline(): void
    {
        $doc = $this->parser->parse('This is _underlined_ text');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<u>underlined</u>', $html);
    }

    public function testMonospace(): void
    {
        $doc = $this->parser->parse('This is =monospace= text');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<code>monospace</code>', $html);
    }

    public function testSuperscript(): void
    {
        $doc = $this->parser->parse('E=mc<sup>2</sup>');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<sup>2</sup>', $html);
    }

    public function testSubscript(): void
    {
        $doc = $this->parser->parse('H<sub>2</sub>O');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<sub>2</sub>', $html);
    }

    // ---------------------------------------------------------------
    // Block elements
    // ---------------------------------------------------------------

    public function testHeadingLevel1(): void
    {
        $doc = $this->parser->parse('+ Heading 1');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<h1>', $html);
        $this->assertStringContainsString('Heading 1', $html);
        $this->assertStringContainsString('</h1>', $html);
    }

    public function testHeadingLevel3(): void
    {
        $doc = $this->parser->parse('+++ Heading 3');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<h3>', $html);
        $this->assertStringContainsString('Heading 3', $html);
        $this->assertStringContainsString('</h3>', $html);
    }

    public function testHorizontalRule(): void
    {
        $doc = $this->parser->parse('---');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<hr />', $html);
    }

    public function testCodeBlock(): void
    {
        $doc = $this->parser->parse("<code>\necho 'hello';\n</code>");
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<pre><code>', $html);
        $this->assertStringContainsString('</code></pre>', $html);
    }

    public function testRawNoop(): void
    {
        $doc = $this->parser->parse('<noop>*not bold* /not italic/</noop>');
        $html = $this->renderer->render($doc);

        // Raw content should be preserved without wiki processing
        $this->assertStringContainsString('*not bold*', $html);
        $this->assertStringNotContainsString('<strong>', $html);
    }

    public function testBlockquote(): void
    {
        $doc = $this->parser->parse('> quoted text');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<blockquote>', $html);
        $this->assertStringContainsString('quoted text', $html);
        $this->assertStringContainsString('</blockquote>', $html);
    }

    // ---------------------------------------------------------------
    // Links
    // ---------------------------------------------------------------

    public function testUrlInline(): void
    {
        $doc = $this->parser->parse('Visit http://example.com today');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<a href="http://example.com">', $html);
        $this->assertStringContainsString('</a>', $html);
    }

    public function testUrlDescribed(): void
    {
        $doc = $this->parser->parse('((http://example.com)(Example Site))');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<a href="http://example.com">', $html);
        $this->assertStringContainsString('Example Site', $html);
        $this->assertStringContainsString('</a>', $html);
    }

    public function testUrlBareParens(): void
    {
        $doc = $this->parser->parse('((http://example.com))');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<a href="http://example.com">', $html);
        $this->assertStringContainsString('</a>', $html);
    }

    public function testWikilink(): void
    {
        $doc = $this->parser->parse('((SomePage)(link text))');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('SomePage', $html);
        $this->assertStringContainsString('link text', $html);
    }

    public function testWikilinkBare(): void
    {
        $doc = $this->parser->parse('((MyPage))');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('MyPage', $html);
    }

    // ---------------------------------------------------------------
    // Lists
    // ---------------------------------------------------------------

    public function testBulletList(): void
    {
        $doc = $this->parser->parse("* Item 1\n* Item 2\n* Item 3");
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('<li>', $html);
        $this->assertStringContainsString('Item 1', $html);
        $this->assertStringContainsString('Item 2', $html);
        $this->assertStringContainsString('Item 3', $html);
        $this->assertStringContainsString('</ul>', $html);
    }

    public function testNumberedList(): void
    {
        $doc = $this->parser->parse("# First\n# Second\n# Third");
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<li>', $html);
        $this->assertStringContainsString('First', $html);
        $this->assertStringContainsString('Second', $html);
        $this->assertStringContainsString('Third', $html);
    }

    public function testNestedList(): void
    {
        $doc = $this->parser->parse("* Outer\n * Inner\n* Back");
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('Outer', $html);
        $this->assertStringContainsString('Inner', $html);
        $this->assertStringContainsString('Back', $html);
    }

    // ---------------------------------------------------------------
    // Tables
    // ---------------------------------------------------------------

    public function testTable(): void
    {
        $doc = $this->parser->parse("<table>\n<tr><th>Header 1</th><th>Header 2</th></tr>\n<tr><td>Cell 1</td><td>Cell 2</td></tr>\n</table>");
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('<tr>', $html);
        $this->assertStringContainsString('Header 1', $html);
        $this->assertStringContainsString('Cell 1', $html);
        $this->assertStringContainsString('</table>', $html);
    }

    // ---------------------------------------------------------------
    // Miscellaneous
    // ---------------------------------------------------------------

    public function testToc(): void
    {
        $doc = $this->parser->parse('<toc>');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<div class="toc">', $html);
    }

    public function testMixedFormatting(): void
    {
        $doc = $this->parser->parse('*bold* and /italic/ text');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<strong>bold</strong>', $html);
        $this->assertStringContainsString('<em>italic</em>', $html);
    }

    public function testGetFormat(): void
    {
        $this->assertSame('cowiki', $this->parser->getFormat());
    }
}
