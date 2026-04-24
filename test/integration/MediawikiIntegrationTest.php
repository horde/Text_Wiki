<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Test\Integration;

use Horde\Text\Wiki\Mediawiki\MediawikiParser;
use Horde\Text\Wiki\Renderer\Xhtml;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests: MediaWiki parser → XHTML renderer
 *
 * Verifies that MediaWiki wikitext is correctly parsed into AST
 * and rendered to XHTML for all supported token types.
 *
 * @coversNothing
 */
class MediawikiIntegrationTest extends TestCase
{
    private MediawikiParser $parser;
    private Xhtml $renderer;

    protected function setUp(): void
    {
        $this->parser = new MediawikiParser();
        $this->renderer = new Xhtml();
    }

    private function render(string $input): string
    {
        return $this->renderer->render($this->parser->parse($input));
    }

    // ---------------------------------------------------------------
    // Inline formatting — apostrophe state machine
    // ---------------------------------------------------------------

    public function testBold(): void
    {
        $result = $this->render("'''bold text'''");
        $this->assertStringContainsString('<strong>bold text</strong>', $result);
    }

    public function testItalic(): void
    {
        $result = $this->render("''italic text''");
        $this->assertStringContainsString('<em>italic text</em>', $result);
    }

    public function testBoldItalic(): void
    {
        $result = $this->render("'''''bold italic'''''");
        $this->assertStringContainsString('<strong>', $result);
        $this->assertStringContainsString('<em>', $result);
        $this->assertStringContainsString('bold italic', $result);
    }

    public function testBoldAndItalicSeparate(): void
    {
        $result = $this->render("'''bold''' and ''italic''");
        $this->assertStringContainsString('<strong>bold</strong>', $result);
        $this->assertStringContainsString('<em>italic</em>', $result);
    }

    // ---------------------------------------------------------------
    // Inline formatting — HTML subset tags
    // ---------------------------------------------------------------

    public function testUnderline(): void
    {
        $result = $this->render('<u>underlined</u>');
        $this->assertStringContainsString('<u>underlined</u>', $result);
    }

    public function testMonospace(): void
    {
        $result = $this->render('<tt>monospace</tt>');
        $this->assertStringContainsString('<code>monospace</code>', $result);
    }

    public function testSuperscript(): void
    {
        $result = $this->render('E=mc<sup>2</sup>');
        $this->assertStringContainsString('<sup>2</sup>', $result);
    }

    public function testSubscript(): void
    {
        $result = $this->render('H<sub>2</sub>O');
        $this->assertStringContainsString('<sub>2</sub>', $result);
    }

    public function testStrikethrough(): void
    {
        $result = $this->render('<s>struck</s>');
        $this->assertStringContainsString('<del>struck</del>', $result);
    }

    public function testDel(): void
    {
        $result = $this->render('<del>deleted</del>');
        $this->assertStringContainsString('<del>deleted</del>', $result);
    }

    public function testIns(): void
    {
        $result = $this->render('<ins>inserted</ins>');
        $this->assertStringContainsString('<ins>inserted</ins>', $result);
    }

    public function testBreak(): void
    {
        $result = $this->render('Line one<br />Line two');
        $this->assertStringContainsString('<br />', $result);
    }

    // ---------------------------------------------------------------
    // Links
    // ---------------------------------------------------------------

    public function testWikilinkDescribed(): void
    {
        $result = $this->render('[[Main Page|Go home]]');
        $this->assertStringContainsString('<a href="Main+Page">', $result);
        $this->assertStringContainsString('Go home</a>', $result);
    }

    public function testWikilinkBare(): void
    {
        $result = $this->render('[[Main Page]]');
        $this->assertStringContainsString('<a href="Main+Page">', $result);
        $this->assertStringContainsString('Main Page</a>', $result);
    }

    public function testWikilinkWithAnchor(): void
    {
        $result = $this->render('[[Page#section|go to section]]');
        $this->assertStringContainsString('href="Page#section"', $result);
        $this->assertStringContainsString('go to section', $result);
    }

    public function testUrlDescribed(): void
    {
        $result = $this->render('[http://example.com Example Site]');
        $this->assertStringContainsString('href="http://example.com"', $result);
        $this->assertStringContainsString('Example Site</a>', $result);
    }

    public function testUrlBare(): void
    {
        $result = $this->render('Visit http://example.com today');
        $this->assertStringContainsString('href="http://example.com"', $result);
    }

    public function testEmail(): void
    {
        $result = $this->render('[[mailto:user@example.com|Email me]]');
        $this->assertStringContainsString('mailto:user@example.com', $result);
        $this->assertStringContainsString('Email me', $result);
    }

    // ---------------------------------------------------------------
    // Images
    // ---------------------------------------------------------------

    public function testImageWithAlt(): void
    {
        $result = $this->render('[[File:photo.png|A nice photo]]');
        $this->assertStringContainsString('<img', $result);
        $this->assertStringContainsString('src="photo.png"', $result);
        $this->assertStringContainsString('alt="A nice photo"', $result);
    }

    public function testImageBare(): void
    {
        $result = $this->render('[[Image:logo.jpg]]');
        $this->assertStringContainsString('<img', $result);
        $this->assertStringContainsString('src="logo.jpg"', $result);
    }

    // ---------------------------------------------------------------
    // Headings
    // ---------------------------------------------------------------

    public function testHeadingLevel1(): void
    {
        $result = $this->render('= Heading 1 =');
        $this->assertStringContainsString('<h1>', $result);
        $this->assertStringContainsString('Heading 1', $result);
        $this->assertStringContainsString('</h1>', $result);
    }

    public function testHeadingLevel2(): void
    {
        $result = $this->render('== Heading 2 ==');
        $this->assertStringContainsString('<h2>', $result);
        $this->assertStringContainsString('Heading 2', $result);
    }

    public function testHeadingLevel3(): void
    {
        $result = $this->render('=== Heading 3 ===');
        $this->assertStringContainsString('<h3>', $result);
        $this->assertStringContainsString('Heading 3', $result);
    }

    // ---------------------------------------------------------------
    // Horizontal rule
    // ---------------------------------------------------------------

    public function testHorizontalRule(): void
    {
        $result = $this->render('----');
        $this->assertStringContainsString('<hr />', $result);
    }

    // ---------------------------------------------------------------
    // Code blocks
    // ---------------------------------------------------------------

    public function testCodeBlock(): void
    {
        $result = $this->render("<code>\necho hello\n</code>");
        $this->assertStringContainsString('<pre><code>', $result);
        $this->assertStringContainsString('echo hello', $result);
    }

    public function testCodeBlockWithLanguage(): void
    {
        $result = $this->render("<code php>\necho hello\n</code>");
        $this->assertStringContainsString('class="language-php"', $result);
    }

    public function testPreBlock(): void
    {
        $result = $this->render("<pre>\nformatted text\n</pre>");
        $this->assertStringContainsString('<pre>', $result);
        $this->assertStringContainsString('formatted text', $result);
    }

    public function testNowikiBlock(): void
    {
        $result = $this->render("<nowiki>\n'''not bold'''\n</nowiki>");
        $this->assertStringNotContainsString('<strong>', $result);
        // Apostrophes are HTML-escaped by the Xhtml renderer
        $this->assertStringContainsString('not bold', $result);
    }

    // ---------------------------------------------------------------
    // Blockquote
    // ---------------------------------------------------------------

    public function testBlockquote(): void
    {
        $result = $this->render('<blockquote>quoted text</blockquote>');
        $this->assertStringContainsString('<blockquote>', $result);
        $this->assertStringContainsString('quoted text', $result);
    }

    // ---------------------------------------------------------------
    // Alignment
    // ---------------------------------------------------------------

    public function testCenterAlignment(): void
    {
        $result = $this->render('<div style="text-align:center;">Centered</div>');
        $this->assertStringContainsString('<div align="center">', $result);
        $this->assertStringContainsString('Centered', $result);
    }

    public function testLeftAlignment(): void
    {
        $result = $this->render('<div style="text-align:left;">Left text</div>');
        $this->assertStringContainsString('<div align="left">', $result);
    }

    public function testRightAlignment(): void
    {
        $result = $this->render('<div style="text-align:right;">Right text</div>');
        $this->assertStringContainsString('<div align="right">', $result);
    }

    public function testJustifyAlignment(): void
    {
        $result = $this->render('<div style="text-align:justify;">Justified</div>');
        $this->assertStringContainsString('<div align="justify">', $result);
    }

    // ---------------------------------------------------------------
    // Styling (color, font, size)
    // ---------------------------------------------------------------

    public function testColor(): void
    {
        $result = $this->render('<span style="color:red;">Red text</span>');
        $this->assertStringContainsString('style="color: red;"', $result);
        $this->assertStringContainsString('Red text', $result);
    }

    public function testFont(): void
    {
        $result = $this->render('<span style="font-family:serif;">Serif text</span>');
        $this->assertStringContainsString('style="font-family: serif"', $result);
        $this->assertStringContainsString('Serif text', $result);
    }

    public function testSize(): void
    {
        $result = $this->render('<span style="font-size:14;">Big text</span>');
        $this->assertStringContainsString('font-size:', $result);
        $this->assertStringContainsString('Big text', $result);
    }

    // ---------------------------------------------------------------
    // Anchor
    // ---------------------------------------------------------------

    public function testAnchor(): void
    {
        $result = $this->render('<span id="my-anchor"></span>');
        $this->assertStringContainsString('id="my-anchor"', $result);
    }

    // ---------------------------------------------------------------
    // Lists
    // ---------------------------------------------------------------

    public function testBulletList(): void
    {
        $result = $this->render("* Item 1\n* Item 2\n* Item 3");
        $this->assertStringContainsString('<ul>', $result);
        $this->assertStringContainsString('<li>', $result);
        $this->assertStringContainsString('Item 1', $result);
        $this->assertStringContainsString('Item 2', $result);
        $this->assertStringContainsString('Item 3', $result);
    }

    public function testNumberedList(): void
    {
        $result = $this->render("# First\n# Second\n# Third");
        $this->assertStringContainsString('<ol>', $result);
        $this->assertStringContainsString('<li>', $result);
        $this->assertStringContainsString('First', $result);
    }

    public function testNestedList(): void
    {
        $result = $this->render("* Outer\n** Inner\n* Back");
        $this->assertStringContainsString('<ul>', $result);
        $this->assertStringContainsString('Outer', $result);
        $this->assertStringContainsString('Inner', $result);
        $this->assertStringContainsString('Back', $result);
    }

    public function testDeepNestedList(): void
    {
        $result = $this->render("* L1\n** L2\n*** L3");
        $this->assertStringContainsString('<ul>', $result);
        $this->assertStringContainsString('L1', $result);
        $this->assertStringContainsString('L3', $result);
    }

    public function testMixedList(): void
    {
        $result = $this->render("* Bullet\n*# Numbered inside");
        $this->assertStringContainsString('<ul>', $result);
        $this->assertStringContainsString('<ol>', $result);
        $this->assertStringContainsString('Bullet', $result);
        $this->assertStringContainsString('Numbered inside', $result);
    }

    // ---------------------------------------------------------------
    // Tables
    // ---------------------------------------------------------------

    public function testSimpleTable(): void
    {
        $input = "{|\n! Header 1 !! Header 2\n|-\n| Cell 1 || Cell 2\n|}";
        $result = $this->render($input);
        $this->assertStringContainsString('<table>', $result);
        $this->assertStringContainsString('<th>', $result);
        $this->assertStringContainsString('Header 1', $result);
        $this->assertStringContainsString('<td>', $result);
        $this->assertStringContainsString('Cell 1', $result);
    }

    public function testHeaderOnlyTable(): void
    {
        $input = "{|\n! Col A !! Col B\n|}";
        $result = $this->render($input);
        $this->assertStringContainsString('<th>', $result);
        $this->assertStringContainsString('Col A', $result);
        $this->assertStringContainsString('Col B', $result);
    }

    public function testMultiRowTable(): void
    {
        $input = "{|\n| A1 || B1\n|-\n| A2 || B2\n|-\n| A3 || B3\n|}";
        $result = $this->render($input);
        $this->assertStringContainsString('<tr>', $result);
        $this->assertStringContainsString('A1', $result);
        $this->assertStringContainsString('A2', $result);
        $this->assertStringContainsString('A3', $result);
    }

    // ---------------------------------------------------------------
    // Definition lists
    // ---------------------------------------------------------------

    public function testDeflistInline(): void
    {
        $result = $this->render("; Term : Definition");
        $this->assertStringContainsString('<dl>', $result);
        $this->assertStringContainsString('<dt>', $result);
        $this->assertStringContainsString('Term', $result);
        $this->assertStringContainsString('<dd>', $result);
        $this->assertStringContainsString('Definition', $result);
    }

    public function testDeflistMultiLine(): void
    {
        $result = $this->render("; Term\n: Definition");
        $this->assertStringContainsString('<dl>', $result);
        $this->assertStringContainsString('<dt>', $result);
        $this->assertStringContainsString('Term', $result);
        $this->assertStringContainsString('<dd>', $result);
        $this->assertStringContainsString('Definition', $result);
    }

    // ---------------------------------------------------------------
    // TOC
    // ---------------------------------------------------------------

    public function testTocMagicWord(): void
    {
        $result = $this->render('__TOC__');
        $this->assertSame('', $result);
    }

    // ---------------------------------------------------------------
    // Comments
    // ---------------------------------------------------------------

    public function testCommentStripped(): void
    {
        $result = $this->render('Before<!-- hidden -->After');
        $this->assertStringNotContainsString('hidden', $result);
        $this->assertStringContainsString('Before', $result);
        $this->assertStringContainsString('After', $result);
    }

    // ---------------------------------------------------------------
    // YouTube
    // ---------------------------------------------------------------

    public function testYoutubeUrl(): void
    {
        $result = $this->render('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
        $this->assertStringContainsString('<iframe', $result);
        $this->assertStringContainsString('dQw4w9WgXcQ', $result);
    }

    public function testYoutubeShortUrl(): void
    {
        $result = $this->render('https://youtu.be/dQw4w9WgXcQ');
        $this->assertStringContainsString('<iframe', $result);
        $this->assertStringContainsString('dQw4w9WgXcQ', $result);
    }

    // ---------------------------------------------------------------
    // Complex document
    // ---------------------------------------------------------------

    public function testComplexDocument(): void
    {
        $source = <<<'WIKI'
            == Main Heading ==

            Some paragraph with '''bold''' text.

            === Sub Heading ===

            * Item 1
            * Item 2
            ** Nested item

            [http://example.com Visit us]

            ----

            <blockquote>A famous quote</blockquote>

            <code>
            echo hello
            </code>
            WIKI;

        $result = $this->render($source);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('<h2>', $result);
        $this->assertStringContainsString('<strong>bold</strong>', $result);
        $this->assertStringContainsString('<h3>', $result);
        $this->assertStringContainsString('<ul>', $result);
        $this->assertStringContainsString('<li>', $result);
        $this->assertStringContainsString('<a href', $result);
        $this->assertStringContainsString('<hr />', $result);
        $this->assertStringContainsString('<blockquote>', $result);
        $this->assertStringContainsString('<pre><code>', $result);
    }

    // ---------------------------------------------------------------
    // Format
    // ---------------------------------------------------------------

    public function testGetFormat(): void
    {
        $this->assertSame('mediawiki', $this->parser->getFormat());
    }
}
