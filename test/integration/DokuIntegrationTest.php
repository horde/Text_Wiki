<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Test\Integration;

use Horde\Text\Wiki\Doku\DokuParser;
use Horde\Text\Wiki\Renderer\Xhtml;
use PHPUnit\Framework\TestCase;

/**
 * Integration test: DokuWiki markup -> Parser -> AST -> Xhtml Renderer
 *
 * Tests the complete pipeline with modern typed AST.
 * @coversNothing
 */
class DokuIntegrationTest extends TestCase
{
    private DokuParser $parser;
    private Xhtml $renderer;

    protected function setUp(): void
    {
        $this->parser = new DokuParser();
        $this->renderer = new Xhtml();
    }

    // ---------------------------------------------------------------
    // Inline formatting
    // ---------------------------------------------------------------

    public function testBold(): void
    {
        $doc = $this->parser->parse('This is **bold** text');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<strong>bold</strong>', $html);
    }

    public function testItalic(): void
    {
        $doc = $this->parser->parse('This is //italic// text');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<em>italic</em>', $html);
    }

    public function testUnderline(): void
    {
        $doc = $this->parser->parse('This is __underlined__ text');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<u>underlined</u>', $html);
    }

    public function testMonospace(): void
    {
        $doc = $this->parser->parse("This is ''monospace'' text");
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<tt>monospace</tt>', $html);
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

    public function testStrikethrough(): void
    {
        $doc = $this->parser->parse('This is <del>deleted</del> text');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<del>deleted</del>', $html);
    }

    // ---------------------------------------------------------------
    // Block elements
    // ---------------------------------------------------------------

    public function testHeadingLevel1(): void
    {
        $doc = $this->parser->parse('====== Heading 1 ======');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<h1>', $html);
        $this->assertStringContainsString('Heading 1', $html);
        $this->assertStringContainsString('</h1>', $html);
    }

    public function testHeadingLevel3(): void
    {
        $doc = $this->parser->parse('==== Heading 3 ====');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<h3>', $html);
        $this->assertStringContainsString('Heading 3', $html);
        $this->assertStringContainsString('</h3>', $html);
    }

    public function testHeadingLevel5(): void
    {
        $doc = $this->parser->parse('== Heading 5 ==');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<h5>', $html);
        $this->assertStringContainsString('Heading 5', $html);
        $this->assertStringContainsString('</h5>', $html);
    }

    public function testHorizontalRule(): void
    {
        $doc = $this->parser->parse('----');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<hr />', $html);
    }

    public function testCodeBlock(): void
    {
        $doc = $this->parser->parse("<code>\necho hello\n</code>");
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<pre><code>', $html);
        $this->assertStringContainsString('echo', $html);
        $this->assertStringContainsString('hello', $html);
        $this->assertStringContainsString('</code></pre>', $html);
    }

    public function testCodeBlockWithLanguage(): void
    {
        $doc = $this->parser->parse("<code php>\necho 'hello';\n</code>");
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<pre><code>', $html);
        $this->assertStringContainsString('echo', $html);
    }

    public function testNowikiBlock(): void
    {
        $doc = $this->parser->parse("<nowiki>\n**not bold**\n</nowiki>");
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('**not bold**', $html);
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

    public function testCenter(): void
    {
        $doc = $this->parser->parse('::centered text::');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('centered text', $html);
        $this->assertStringContainsString('center', $html);
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
        $doc = $this->parser->parse('[[http://example.com|Example Site]]');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<a href="http://example.com">', $html);
        $this->assertStringContainsString('Example Site', $html);
        $this->assertStringContainsString('</a>', $html);
    }

    public function testUrlBare(): void
    {
        $doc = $this->parser->parse('[[http://example.com]]');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<a href="http://example.com">', $html);
        $this->assertStringContainsString('</a>', $html);
    }

    public function testWikilinkDescribed(): void
    {
        $doc = $this->parser->parse('[[SomePage|link text]]');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('SomePage', $html);
        $this->assertStringContainsString('link text', $html);
    }

    public function testWikilinkBare(): void
    {
        $doc = $this->parser->parse('[[MyPage]]');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('MyPage', $html);
    }

    public function testWikilinkWithAnchor(): void
    {
        $doc = $this->parser->parse('[[SomePage#section|go to section]]');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('go to section', $html);
    }

    // ---------------------------------------------------------------
    // Image
    // ---------------------------------------------------------------

    public function testImage(): void
    {
        $doc = $this->parser->parse('{{http://example.com/img.png|Photo}}');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('http://example.com/img.png', $html);
    }

    public function testImageBare(): void
    {
        $doc = $this->parser->parse('{{http://example.com/img.png}}');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('http://example.com/img.png', $html);
    }

    // ---------------------------------------------------------------
    // Lists (indentation-based in DokuWiki)
    // ---------------------------------------------------------------

    public function testBulletList(): void
    {
        $doc = $this->parser->parse("  * Item 1\n  * Item 2\n  * Item 3");
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
        $doc = $this->parser->parse("  - First\n  - Second\n  - Third");
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<li>', $html);
        $this->assertStringContainsString('First', $html);
        $this->assertStringContainsString('Second', $html);
        $this->assertStringContainsString('Third', $html);
    }

    public function testNestedBulletList(): void
    {
        $doc = $this->parser->parse("  * Outer\n    * Inner\n  * Back");
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('Outer', $html);
        $this->assertStringContainsString('Inner', $html);
        $this->assertStringContainsString('Back', $html);
    }

    public function testDeepNestedList(): void
    {
        $doc = $this->parser->parse("  * L1\n    * L2\n      * L3\n    * L2b\n  * L1b");
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('L1', $html);
        $this->assertStringContainsString('L2', $html);
        $this->assertStringContainsString('L3', $html);
        $this->assertStringContainsString('L2b', $html);
        $this->assertStringContainsString('L1b', $html);
    }

    // ---------------------------------------------------------------
    // Tables (| data, ^ header)
    // ---------------------------------------------------------------

    public function testTable(): void
    {
        $doc = $this->parser->parse("^ Header 1 ^ Header 2 ^\n| Cell 1 | Cell 2 |");
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('<tr>', $html);
        $this->assertStringContainsString('Header 1', $html);
        $this->assertStringContainsString('Cell 1', $html);
        $this->assertStringContainsString('</table>', $html);
    }

    public function testTableHeaderCells(): void
    {
        $doc = $this->parser->parse("^ Name ^ Value ^\n| Alpha | 1 |");
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<th>', $html);
        $this->assertStringContainsString('Name', $html);
        $this->assertStringContainsString('Value', $html);
    }

    // ---------------------------------------------------------------
    // Definition lists
    // ---------------------------------------------------------------

    public function testDeflist(): void
    {
        $doc = $this->parser->parse("; Term ; Definition\n");
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<dt>', $html);
        $this->assertStringContainsString('Term', $html);
        $this->assertStringContainsString('<dd>', $html);
        $this->assertStringContainsString('Definition', $html);
    }

    // ---------------------------------------------------------------
    // Nowiki / Escape
    // ---------------------------------------------------------------

    public function testInlineNowiki(): void
    {
        $doc = $this->parser->parse('This is %%**not bold**%% text');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('**not bold**', $html);
        $this->assertStringNotContainsString('<strong>', $html);
    }

    // ---------------------------------------------------------------
    // Miscellaneous
    // ---------------------------------------------------------------

    public function testMixedFormatting(): void
    {
        $doc = $this->parser->parse('**bold** and //italic// text');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<strong>bold</strong>', $html);
        $this->assertStringContainsString('<em>italic</em>', $html);
    }

    public function testGetFormat(): void
    {
        $this->assertSame('doku', $this->parser->getFormat());
    }
}
