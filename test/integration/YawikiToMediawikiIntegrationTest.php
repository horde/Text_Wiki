<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Test\Integration;

use Horde\Text\Wiki\Yawiki\YawikiParser;
use Horde\Text\Wiki\Renderer\Mediawiki;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests: Yawiki parser → MediaWiki renderer
 *
 * Verifies that Yawiki wikitext is correctly parsed into AST
 * and rendered to MediaWiki markup for all supported token types.
 *
 * @coversNothing
 */
class YawikiToMediawikiIntegrationTest extends TestCase
{
    private YawikiParser $parser;
    private Mediawiki $renderer;

    protected function setUp(): void
    {
        $this->parser = new YawikiParser();
        $this->renderer = new Mediawiki();
    }

    private function render(string $input): string
    {
        return $this->renderer->render($this->parser->parse($input));
    }

    // ---------------------------------------------------------------
    // Inline formatting
    // ---------------------------------------------------------------

    public function testBold(): void
    {
        $result = $this->render("'''bold text'''");
        $this->assertStringContainsString("'''bold text'''", $result);
    }

    public function testItalic(): void
    {
        $result = $this->render("''italic text''");
        $this->assertStringContainsString("''italic text''", $result);
    }

    public function testStrong(): void
    {
        $result = $this->render('**strong text**');
        $this->assertStringContainsString("'''strong text'''", $result);
    }

    public function testEmphasis(): void
    {
        $result = $this->render('//emphasis text//');
        $this->assertStringContainsString("''emphasis text''", $result);
    }

    public function testUnderline(): void
    {
        $result = $this->render('__underlined__');
        $this->assertStringContainsString('<u>underlined</u>', $result);
    }

    public function testMonospace(): void
    {
        $result = $this->render('{{monospace}}');
        $this->assertStringContainsString('<tt>monospace</tt>', $result);
    }

    public function testSuperscript(): void
    {
        $result = $this->render('E=mc^^2^^');
        $this->assertStringContainsString('<sup>2</sup>', $result);
    }

    public function testSubscript(): void
    {
        $result = $this->render('H,,2,,O');
        $this->assertStringContainsString('<sub>2</sub>', $result);
    }

    public function testBreak(): void
    {
        $result = $this->render("Line one _\nLine two");
        $this->assertStringContainsString('<br />', $result);
    }

    // ---------------------------------------------------------------
    // Headings
    // ---------------------------------------------------------------

    public function testHeading1(): void
    {
        $result = $this->render('+ Main Title');
        $this->assertStringContainsString('= Main Title =', $result);
    }

    public function testHeading2(): void
    {
        $result = $this->render('++ Section');
        $this->assertStringContainsString('== Section ==', $result);
    }

    public function testHeading3(): void
    {
        $result = $this->render('+++ Subsection');
        $this->assertStringContainsString('=== Subsection ===', $result);
    }

    // ---------------------------------------------------------------
    // Links
    // ---------------------------------------------------------------

    public function testUrlDescribed(): void
    {
        $result = $this->render('[http://example.com Example Site]');
        $this->assertStringContainsString('[http://example.com Example Site]', $result);
    }

    public function testFreelink(): void
    {
        $result = $this->render('((Free Link))');
        $this->assertStringContainsString('[[Free Link]]', $result);
    }

    public function testPhplookup(): void
    {
        $result = $this->render('[[php strlen]]');
        $this->assertStringContainsString('[[php strlen]]', $result);
    }

    // ---------------------------------------------------------------
    // Block elements
    // ---------------------------------------------------------------

    public function testHorizontalRule(): void
    {
        $result = $this->render('----');
        $this->assertStringContainsString('----', $result);
    }

    public function testCodeBlock(): void
    {
        $result = $this->render("<code>\necho hello\n</code>");
        $this->assertStringContainsString('<code>', $result);
        $this->assertStringContainsString('echo hello', $result);
        $this->assertStringContainsString('</code>', $result);
    }

    public function testBlockquote(): void
    {
        $result = $this->render('> quoted text');
        $this->assertStringContainsString('<blockquote>', $result);
        $this->assertStringContainsString('quoted text', $result);
        $this->assertStringContainsString('</blockquote>', $result);
    }

    // ---------------------------------------------------------------
    // Lists
    // ---------------------------------------------------------------

    public function testBulletList(): void
    {
        $result = $this->render("* Item 1\n* Item 2\n* Item 3");
        $this->assertStringContainsString('* Item 1', $result);
        $this->assertStringContainsString('* Item 2', $result);
    }

    public function testNumberedList(): void
    {
        $result = $this->render("# First\n# Second\n# Third");
        $this->assertStringContainsString('# First', $result);
        $this->assertStringContainsString('# Second', $result);
    }

    // ---------------------------------------------------------------
    // Tables
    // ---------------------------------------------------------------

    public function testSimpleTable(): void
    {
        $input = "|| ~ Header 1 || ~ Header 2 ||\n|| Cell 1 || Cell 2 ||";
        $result = $this->render($input);
        $this->assertStringContainsString('{|', $result);
        $this->assertStringContainsString('Header 1', $result);
        $this->assertStringContainsString('Cell 1', $result);
        $this->assertStringContainsString('|}', $result);
    }

    // ---------------------------------------------------------------
    // Definition lists
    // ---------------------------------------------------------------

    public function testDeflist(): void
    {
        $result = $this->render(': Term : Definition');
        $this->assertStringContainsString('; Term', $result);
        $this->assertStringContainsString(': Definition', $result);
    }

    // ---------------------------------------------------------------
    // Colortext
    // ---------------------------------------------------------------

    public function testColortext(): void
    {
        $result = $this->render('##FF0000|Red text##');
        $this->assertStringContainsString('Red text', $result);
    }

    // ---------------------------------------------------------------
    // TOC
    // ---------------------------------------------------------------

    public function testToc(): void
    {
        $result = $this->render('[[toc]]');
        $this->assertStringContainsString('__TOC__', $result);
    }

    // ---------------------------------------------------------------
    // Revise marks
    // ---------------------------------------------------------------

    public function testReviseDel(): void
    {
        $result = $this->render('@@---deleted text@@');
        $this->assertStringContainsString('<del>deleted text</del>', $result);
    }

    public function testReviseIns(): void
    {
        $result = $this->render('@@+++inserted text@@');
        $this->assertStringContainsString('<ins>inserted text</ins>', $result);
    }

    // ---------------------------------------------------------------
    // Complex document
    // ---------------------------------------------------------------

    public function testComplexDocument(): void
    {
        $source = <<<'WIKI'
++ Main Heading

Some paragraph with '''bold''' text.

* Item 1
* Item 2

----

> A famous quote
WIKI;

        $result = $this->render($source);
        $this->assertStringContainsString('== Main Heading ==', $result);
        $this->assertStringContainsString("'''bold'''", $result);
        $this->assertStringContainsString('* Item 1', $result);
        $this->assertStringContainsString('----', $result);
        $this->assertStringContainsString('<blockquote>', $result);
    }

    // ---------------------------------------------------------------
    // Format
    // ---------------------------------------------------------------

    public function testGetFormat(): void
    {
        $this->assertSame('mediawiki', $this->renderer->getFormat());
    }
}
