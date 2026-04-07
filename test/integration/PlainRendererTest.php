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
use Horde\Text\Wiki\Cowiki\CowikiParser;
use Horde\Text\Wiki\Renderer\Plain;
use Horde\Text\Wiki\Yawiki\YawikiParser;
use PHPUnit\Framework\TestCase;

/**
 * Plain text renderer integration test
 *
 * Tests AST → Plain text rendering from all three parser dialects.
 * Verifies formatting is stripped, structure is preserved via whitespace,
 * and links show URL context.
 *
 * @coversNothing
 */
class PlainRendererTest extends TestCase
{
    private Plain $renderer;
    private YawikiParser $yawiki;
    private BBCodeParser $bbcode;
    private CowikiParser $cowiki;

    protected function setUp(): void
    {
        $this->renderer = new Plain();
        $this->yawiki = new YawikiParser();
        $this->bbcode = new BBCodeParser();
        $this->cowiki = new CowikiParser();
    }

    // ---------------------------------------------------------------
    // Formatting stripped (Yawiki source)
    // ---------------------------------------------------------------

    public function testBoldStripped(): void
    {
        $doc = $this->yawiki->parse("**bold text**");
        $plain = $this->renderer->render($doc);

        $this->assertStringContainsString('bold text', $plain);
        $this->assertStringNotContainsString('**', $plain);
    }

    public function testItalicStripped(): void
    {
        $doc = $this->yawiki->parse('//italic text//');
        $plain = $this->renderer->render($doc);

        $this->assertStringContainsString('italic text', $plain);
        $this->assertStringNotContainsString('//', $plain);
    }

    public function testUnderlineStripped(): void
    {
        $doc = $this->yawiki->parse('__underlined__');
        $plain = $this->renderer->render($doc);

        $this->assertStringContainsString('underlined', $plain);
        $this->assertStringNotContainsString('__', $plain);
    }

    public function testMonospaceStripped(): void
    {
        $doc = $this->yawiki->parse('{{monospace}}');
        $plain = $this->renderer->render($doc);

        $this->assertStringContainsString('monospace', $plain);
        $this->assertStringNotContainsString('{{', $plain);
    }

    // ---------------------------------------------------------------
    // Formatting stripped (BBCode source)
    // ---------------------------------------------------------------

    public function testBBCodeBoldStripped(): void
    {
        $doc = $this->bbcode->parse('[b]bold[/b]');
        $plain = $this->renderer->render($doc);

        $this->assertStringContainsString('bold', $plain);
        $this->assertStringNotContainsString('[b]', $plain);
    }

    public function testBBCodeStrikeStripped(): void
    {
        $doc = $this->bbcode->parse('[s]struck[/s]');
        $plain = $this->renderer->render($doc);

        $this->assertStringContainsString('struck', $plain);
        $this->assertStringNotContainsString('[s]', $plain);
    }

    public function testBBCodeColorStripped(): void
    {
        $doc = $this->bbcode->parse('[color=red]colored[/color]');
        $plain = $this->renderer->render($doc);

        $this->assertStringContainsString('colored', $plain);
        $this->assertStringNotContainsString('[color', $plain);
    }

    public function testBBCodeFontStripped(): void
    {
        $doc = $this->bbcode->parse('[font=Arial]styled[/font]');
        $plain = $this->renderer->render($doc);

        $this->assertStringContainsString('styled', $plain);
        $this->assertStringNotContainsString('[font', $plain);
    }

    public function testBBCodeSizeStripped(): void
    {
        $doc = $this->bbcode->parse('[size=14]sized[/size]');
        $plain = $this->renderer->render($doc);

        $this->assertStringContainsString('sized', $plain);
        $this->assertStringNotContainsString('[size', $plain);
    }

    // ---------------------------------------------------------------
    // Formatting stripped (Cowiki source)
    // ---------------------------------------------------------------

    public function testCowikiBoldStripped(): void
    {
        $doc = $this->cowiki->parse('*bold*');
        $plain = $this->renderer->render($doc);

        $this->assertStringContainsString('bold', $plain);
        $this->assertStringNotContainsString('*', $plain);
    }

    public function testCowikiItalicStripped(): void
    {
        $doc = $this->cowiki->parse('/italic/');
        $plain = $this->renderer->render($doc);

        $this->assertStringContainsString('italic', $plain);
    }

    // ---------------------------------------------------------------
    // Links — URL context shown
    // ---------------------------------------------------------------

    public function testYawikiUrlWithText(): void
    {
        $doc = $this->yawiki->parse('[http://example.com Visit us]');
        $plain = $this->renderer->render($doc);

        $this->assertStringContainsString('Visit us', $plain);
        $this->assertStringContainsString('http://example.com', $plain);
    }

    public function testBBCodeUrlWithHref(): void
    {
        $doc = $this->bbcode->parse('[url=http://example.com]Click here[/url]');
        $plain = $this->renderer->render($doc);

        $this->assertStringContainsString('Click here', $plain);
        $this->assertStringContainsString('http://example.com', $plain);
    }

    public function testBBCodeUrlBare(): void
    {
        $doc = $this->bbcode->parse('[url]http://example.com[/url]');
        $plain = $this->renderer->render($doc);

        $this->assertStringContainsString('http://example.com', $plain);
    }

    public function testCowikiUrlDescribed(): void
    {
        $doc = $this->cowiki->parse('((http://example.com)(Example))');
        $plain = $this->renderer->render($doc);

        $this->assertStringContainsString('Example', $plain);
        $this->assertStringContainsString('http://example.com', $plain);
    }

    public function testBBCodeEmailWithAttr(): void
    {
        $doc = $this->bbcode->parse('[email=user@example.com]Contact[/email]');
        $plain = $this->renderer->render($doc);

        $this->assertStringContainsString('Contact', $plain);
        $this->assertStringContainsString('user@example.com', $plain);
    }

    public function testCowikiWikilink(): void
    {
        $doc = $this->cowiki->parse('((SomePage)(link text))');
        $plain = $this->renderer->render($doc);

        $this->assertStringContainsString('link text', $plain);
    }

    // ---------------------------------------------------------------
    // Block elements
    // ---------------------------------------------------------------

    public function testHeadingBecomesText(): void
    {
        $doc = $this->yawiki->parse('+ Main Heading');
        $plain = $this->renderer->render($doc);

        $this->assertStringContainsString('Main Heading', $plain);
        $this->assertStringNotContainsString('+', $plain);
    }

    public function testCowikiHeading(): void
    {
        $doc = $this->cowiki->parse('+++ Sub Heading');
        $plain = $this->renderer->render($doc);

        $this->assertStringContainsString('Sub Heading', $plain);
    }

    public function testCodePreserved(): void
    {
        $doc = $this->yawiki->parse("<code>\necho 'hello';\n</code>");
        $plain = $this->renderer->render($doc);

        $this->assertStringContainsString("echo 'hello';", $plain);
        $this->assertStringNotContainsString('<code>', $plain);
    }

    public function testBBCodeCodePreserved(): void
    {
        $doc = $this->bbcode->parse('[code]var x = 1;[/code]');
        $plain = $this->renderer->render($doc);

        $this->assertStringContainsString('var x = 1;', $plain);
        $this->assertStringNotContainsString('[code]', $plain);
    }

    public function testBlockquoteIndented(): void
    {
        $doc = $this->yawiki->parse('> quoted text');
        $plain = $this->renderer->render($doc);

        $this->assertStringContainsString('quoted text', $plain);
    }

    public function testBBCodeBlockquote(): void
    {
        $doc = $this->bbcode->parse('[quote]quoted text[/quote]');
        $plain = $this->renderer->render($doc);

        $this->assertStringContainsString('quoted text', $plain);
    }

    // ---------------------------------------------------------------
    // Lists
    // ---------------------------------------------------------------

    public function testBulletList(): void
    {
        $doc = $this->yawiki->parse("* Item 1\n* Item 2\n* Item 3");
        $plain = $this->renderer->render($doc);

        $this->assertStringContainsString('Item 1', $plain);
        $this->assertStringContainsString('Item 2', $plain);
        $this->assertStringContainsString('Item 3', $plain);
        $this->assertStringContainsString('- ', $plain);
    }

    public function testBBCodeList(): void
    {
        $doc = $this->bbcode->parse("[list]\n[*]Alpha\n[*]Beta\n[/list]");
        $plain = $this->renderer->render($doc);

        $this->assertStringContainsString('Alpha', $plain);
        $this->assertStringContainsString('Beta', $plain);
    }

    public function testCowikiList(): void
    {
        $doc = $this->cowiki->parse("* First\n* Second");
        $plain = $this->renderer->render($doc);

        $this->assertStringContainsString('First', $plain);
        $this->assertStringContainsString('Second', $plain);
    }

    // ---------------------------------------------------------------
    // Suppressed elements
    // ---------------------------------------------------------------

    public function testImageSuppressed(): void
    {
        $doc = $this->bbcode->parse('[img]http://example.com/img.png[/img]');
        $plain = $this->renderer->render($doc);

        $this->assertSame('', trim($plain));
    }

    public function testYoutubeSuppressed(): void
    {
        $doc = $this->bbcode->parse('[youtube]dQw4w9WgXcQ[/youtube]');
        $plain = $this->renderer->render($doc);

        $this->assertSame('', trim($plain));
    }

    public function testTocSuppressed(): void
    {
        $doc = $this->cowiki->parse('<toc>');
        $plain = $this->renderer->render($doc);

        $this->assertSame('', trim($plain));
    }

    // ---------------------------------------------------------------
    // Alignment stripped (BBCode)
    // ---------------------------------------------------------------

    public function testCenterStripped(): void
    {
        $doc = $this->bbcode->parse('[center]centered text[/center]');
        $plain = $this->renderer->render($doc);

        $this->assertStringContainsString('centered text', $plain);
        $this->assertStringNotContainsString('[center]', $plain);
    }

    // ---------------------------------------------------------------
    // Tables
    // ---------------------------------------------------------------

    public function testTablePipeDelimited(): void
    {
        $doc = $this->cowiki->parse("<table>\n<tr><th>Name</th><th>Value</th></tr>\n<tr><td>A</td><td>1</td></tr>\n</table>");
        $plain = $this->renderer->render($doc);

        $this->assertStringContainsString('Name', $plain);
        $this->assertStringContainsString('Value', $plain);
        $this->assertStringContainsString('||', $plain);
    }

    // ---------------------------------------------------------------
    // Mixed documents
    // ---------------------------------------------------------------

    public function testMixedYawikiDocument(): void
    {
        $source = <<<'YAWIKI'
+ Main Heading

Some paragraph with **bold** text.

* Item 1
* Item 2

----

> A famous quote
YAWIKI;

        $doc = $this->yawiki->parse($source);
        $plain = $this->renderer->render($doc);

        $this->assertStringContainsString('Main Heading', $plain);
        $this->assertStringContainsString('bold', $plain);
        $this->assertStringContainsString('Item 1', $plain);
        $this->assertStringContainsString('famous quote', $plain);
        $this->assertStringNotContainsString('**', $plain);
    }

    public function testMixedBBCodeDocument(): void
    {
        $source = <<<'BBCODE'
[b]Bold text[/b] and [i]italic[/i]

[quote]A famous quote[/quote]

[list]
[*]Item 1
[*]Item 2
[/list]

[url=http://example.com]Visit us[/url]

[hr]

[color=red]red text[/color]
BBCODE;

        $doc = $this->bbcode->parse($source);
        $plain = $this->renderer->render($doc);

        $this->assertStringContainsString('Bold text', $plain);
        $this->assertStringContainsString('italic', $plain);
        $this->assertStringContainsString('famous quote', $plain);
        $this->assertStringContainsString('Item 1', $plain);
        $this->assertStringContainsString('Visit us', $plain);
        $this->assertStringContainsString('red text', $plain);
        $this->assertStringNotContainsString('[b]', $plain);
        $this->assertStringNotContainsString('[/i]', $plain);
        $this->assertStringNotContainsString('[color', $plain);
    }

    public function testGetFormat(): void
    {
        $this->assertSame('plain', $this->renderer->getFormat());
    }
}
