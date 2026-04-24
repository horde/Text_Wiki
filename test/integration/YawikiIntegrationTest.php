<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Test\Integration;

use Horde\Text\Wiki\Renderer\Xhtml;
use Horde\Text\Wiki\Yawiki\YawikiParser;
use PHPUnit\Framework\TestCase;

/**
 * Integration test: Yawiki markup → Parser → AST → Xhtml Renderer
 *
 * Tests the complete pipeline with modern typed AST.
 * @coversNothing
 */
class YawikiIntegrationTest extends TestCase
{
    private YawikiParser $parser;
    private Xhtml $renderer;

    protected function setUp(): void
    {
        $this->parser = new YawikiParser();
        $this->renderer = new Xhtml();
    }

    // ---------------------------------------------------------------
    // Inline formatting
    // ---------------------------------------------------------------

    public function testBold(): void
    {
        $doc = $this->parser->parse("'''bold text'''");
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<strong>bold text</strong>', $html);
    }

    public function testItalic(): void
    {
        $doc = $this->parser->parse("''italic text''");
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<em>italic text</em>', $html);
    }

    public function testStrong(): void
    {
        $doc = $this->parser->parse('**strong text**');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<strong>strong text</strong>', $html);
    }

    public function testEmphasis(): void
    {
        $doc = $this->parser->parse('//emphasis text//');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<em>emphasis text</em>', $html);
    }

    public function testUnderline(): void
    {
        $doc = $this->parser->parse('__underlined__');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<u>underlined</u>', $html);
    }

    public function testMonospace(): void
    {
        $doc = $this->parser->parse('{{monospace}}');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<code>monospace</code>', $html);
    }

    public function testSuperscript(): void
    {
        $doc = $this->parser->parse('E=mc^^2^^');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<sup>2</sup>', $html);
    }

    public function testSubscript(): void
    {
        $doc = $this->parser->parse('H,,2,,O');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<sub>2</sub>', $html);
    }

    // ---------------------------------------------------------------
    // Block elements
    // ---------------------------------------------------------------

    public function testHeadingLevel1(): void
    {
        $doc = $this->parser->parse("+ Heading 1");
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<h1>', $html);
        $this->assertStringContainsString('Heading 1', $html);
        $this->assertStringContainsString('</h1>', $html);
    }

    public function testHeadingLevel3(): void
    {
        $doc = $this->parser->parse("+++ Heading 3");
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<h3>', $html);
        $this->assertStringContainsString('Heading 3', $html);
        $this->assertStringContainsString('</h3>', $html);
    }

    public function testHorizontalRule(): void
    {
        $doc = $this->parser->parse("----");
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

    public function testBlockquote(): void
    {
        $doc = $this->parser->parse("> quoted text");
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<blockquote>', $html);
        $this->assertStringContainsString('quoted text', $html);
        $this->assertStringContainsString('</blockquote>', $html);
    }

    // ---------------------------------------------------------------
    // Links
    // ---------------------------------------------------------------

    public function testUrlWithText(): void
    {
        $doc = $this->parser->parse('[http://example.com Example Site]');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<a href="http://example.com">', $html);
        $this->assertStringContainsString('Example Site', $html);
        $this->assertStringContainsString('</a>', $html);
    }

    public function testFreelink(): void
    {
        $doc = $this->parser->parse('((Some Page))');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<a href=', $html);
        $this->assertStringContainsString('Some+Page', $html);
        $this->assertStringContainsString('</a>', $html);
    }

    public function testPhplookup(): void
    {
        $doc = $this->parser->parse('[[php strlen]]');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('https://www.php.net/', $html);
        $this->assertStringContainsString('strlen', $html);
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

        // Yawiki uses list type attribute from tokenizer
        $this->assertStringContainsString('<li>', $html);
        $this->assertStringContainsString('First', $html);
        $this->assertStringContainsString('Second', $html);
        $this->assertStringContainsString('Third', $html);
    }

    // ---------------------------------------------------------------
    // Tables
    // ---------------------------------------------------------------

    public function testTable(): void
    {
        $doc = $this->parser->parse("|| ~ Header 1 || ~ Header 2 ||\n|| Cell 1 || Cell 2 ||");
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

    public function testColortext(): void
    {
        $doc = $this->parser->parse('##red|colored text##');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('style="color:', $html);
        $this->assertStringContainsString('colored text', $html);
    }

    public function testAnchor(): void
    {
        $doc = $this->parser->parse('[[# myanchor]]');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<a id="myanchor">', $html);
    }

    public function testTocEmpty(): void
    {
        $doc = $this->parser->parse('[[toc]]');
        $html = $this->renderer->render($doc);

        $this->assertSame('', $html);
    }

    public function testImage(): void
    {
        $doc = $this->parser->parse('[[image http://example.com/img.png]]');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<img src="http://example.com/img.png"', $html);
    }

    public function testCenter(): void
    {
        $doc = $this->parser->parse("= centered text");
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('center', $html);
        $this->assertStringContainsString('centered text', $html);
    }

    // ---------------------------------------------------------------
    // Combined / complex documents
    // ---------------------------------------------------------------

    public function testMixedFormatting(): void
    {
        // Bold and italic on separate pieces of text
        $doc = $this->parser->parse("**bold text** and //italic text//");
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<strong>bold text</strong>', $html);
        $this->assertStringContainsString('<em>italic text</em>', $html);
    }

    public function testDefinitionList(): void
    {
        $doc = $this->parser->parse(": Term : Definition");
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<dl>', $html);
        $this->assertStringContainsString('<dt>', $html);
        $this->assertStringContainsString('Term', $html);
        $this->assertStringContainsString('<dd>', $html);
        $this->assertStringContainsString('Definition', $html);
        $this->assertStringContainsString('</dl>', $html);
    }

    public function testRevisionMarks(): void
    {
        $doc = $this->parser->parse('@@---old text+++new text@@');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<del>', $html);
        $this->assertStringContainsString('old text', $html);
        $this->assertStringContainsString('<ins>', $html);
        $this->assertStringContainsString('new text', $html);
    }

    public function testGetFormat(): void
    {
        $this->assertSame('yawiki', $this->parser->getFormat());
    }

    // ---------------------------------------------------------------
    // Table of Contents
    // ---------------------------------------------------------------

    public function testTocWithHeadings(): void
    {
        $wiki = <<<'WIKI'
[[toc]]

+ Introduction

Some text.

++ Getting Started

More text.

+++ Installation

Details.
WIKI;
        $doc = $this->parser->parse($wiki);
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<nav id="toc">', $html);
        $this->assertStringContainsString('<h2>Table of Contents</h2>', $html);
        $this->assertStringContainsString('<ol>', $html);
        $this->assertStringContainsString('<a href="#toc-0-introduction">Introduction</a>', $html);
        $this->assertStringContainsString('<a href="#toc-1-getting-started">Getting Started</a>', $html);
        $this->assertStringContainsString('<a href="#toc-2-installation">Installation</a>', $html);
        $this->assertStringContainsString('</nav>', $html);

        $this->assertStringContainsString('<h1 id="toc-0-introduction">', $html);
        $this->assertStringContainsString('<h2 id="toc-1-getting-started">', $html);
        $this->assertStringContainsString('<h3 id="toc-2-installation">', $html);
    }

    public function testTocWithDepth(): void
    {
        $wiki = <<<'WIKI'
[[toc 2]]

+ Top Level

++ Second Level

+++ Third Level
WIKI;
        $doc = $this->parser->parse($wiki);
        $html = $this->renderer->render($doc);

        $navEnd = strpos($html, '</nav>');
        $tocHtml = substr($html, 0, $navEnd);

        $this->assertStringContainsString('<a href="#toc-0-top-level">Top Level</a>', $tocHtml);
        $this->assertStringContainsString('<a href="#toc-1-second-level">Second Level</a>', $tocHtml);
        $this->assertStringNotContainsString('Third Level', $tocHtml, 'Third level should be excluded from TOC by depth limit');

        $this->assertStringContainsString('<h3 id="toc-2-third-level">', $html, 'Third level heading should still render in body');
    }

    public function testHeadingIdsWithoutToc(): void
    {
        $wiki = <<<'WIKI'
+ First

++ Second
WIKI;
        $this->renderer->enableHeadingIds();
        $doc = $this->parser->parse($wiki);
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<h1 id="toc-0-first">', $html);
        $this->assertStringContainsString('<h2 id="toc-1-second">', $html);
        $this->assertStringNotContainsString('<nav', $html);
    }

    public function testHeadingIdsOffByDefault(): void
    {
        $doc = $this->parser->parse('+ Heading');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<h1>Heading</h1>', $html);
        $this->assertStringNotContainsString('id=', $html);
    }

    public function testHeadingIdSlugSpecialCharacters(): void
    {
        $this->renderer->enableHeadingIds();
        $doc = $this->parser->parse('+ Hello World & Goodbye!');
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('id="toc-0-hello-world-goodbye"', $html);
    }

    public function testDuplicateHeadingText(): void
    {
        $wiki = <<<'WIKI'
+ Overview

+ Overview
WIKI;
        $this->renderer->enableHeadingIds();
        $doc = $this->parser->parse($wiki);
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('id="toc-0-overview"', $html);
        $this->assertStringContainsString('id="toc-1-overview"', $html);
    }

    public function testTocNestedOlStructure(): void
    {
        $wiki = <<<'WIKI'
[[toc]]

+ Level 1

++ Level 2a

++ Level 2b

+ Level 1 Again
WIKI;
        $doc = $this->parser->parse($wiki);
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<nav id="toc">', $html);
        $occurrences = substr_count($html, '<ol>');
        $closingOccurrences = substr_count($html, '</ol>');
        $this->assertSame($occurrences, $closingOccurrences, 'ol tags must be balanced');

        $liCount = substr_count($html, '<li>');
        $liCloseCount = substr_count($html, '</li>');
        $this->assertSame($liCount, $liCloseCount, 'li tags must be balanced');
        $this->assertSame(4, $liCount, 'Should have 4 list items');
    }
}
