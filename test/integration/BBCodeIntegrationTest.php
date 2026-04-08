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
 * @coversNothing
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

    public function testOrderedListNumeric(): void
    {
        $bbcode = "[list=1]\n[*]First\n[*]Second\n[*]Third\n[/list]";
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        // Should use <ol> for ordered list
        $html = str_replace("\n", '', $html);
        $this->assertStringContainsString('<ol>', $html);
        $this->assertStringContainsString('<li>First</li>', $html);
        $this->assertStringContainsString('<li>Second</li>', $html);
        $this->assertStringContainsString('<li>Third</li>', $html);
        $this->assertStringContainsString('</ol>', $html);
        $this->assertStringNotContainsString('<ul>', $html);
    }

    public function testOrderedListLowercaseAlpha(): void
    {
        $bbcode = "[list=a]\n[*]Alpha\n[*]Beta\n[*]Gamma\n[/list]";
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        $html = str_replace("\n", '', $html);
        $this->assertStringContainsString('<ol type="a">', $html);
        $this->assertStringContainsString('<li>Alpha</li>', $html);
        $this->assertStringContainsString('</ol>', $html);
    }

    public function testOrderedListUppercaseAlpha(): void
    {
        $bbcode = "[list=A]\n[*]First\n[*]Second\n[/list]";
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        $html = str_replace("\n", '', $html);
        $this->assertStringContainsString('<ol type="A">', $html);
        $this->assertStringContainsString('<li>First</li>', $html);
        $this->assertStringContainsString('</ol>', $html);
    }

    public function testNestedLists(): void
    {
        $bbcode = "[list]\n[*]Outer 1\n[list]\n[*]Inner 1\n[*]Inner 2\n[/list]\n[*]Outer 2\n[/list]";
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        $html = str_replace("\n", '', $html);
        // Should have nested <ul> tags
        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('<li>Outer 1<ul>', $html);
        $this->assertStringContainsString('<li>Inner 1</li>', $html);
        $this->assertStringContainsString('<li>Inner 2</li>', $html);
        $this->assertStringContainsString('</ul></li>', $html);
        $this->assertStringContainsString('<li>Outer 2</li>', $html);
    }

    public function testMixedNestedLists(): void
    {
        $bbcode = "[list=1]\n[*]Numbered\n[list]\n[*]Bulleted inside\n[/list]\n[*]More numbered\n[/list]";
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        $html = str_replace("\n", '', $html);
        // Should have <ol> containing <ul>
        $this->assertStringContainsString('<ol>', $html);
        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('<li>Numbered<ul>', $html);
        $this->assertStringContainsString('<li>Bulleted inside</li>', $html);
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

    public function testStrikethrough(): void
    {
        $bbcode = '[s]strikethrough text[/s]';
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        $this->assertSame('<del>strikethrough text</del>', $html);
    }

    public function testSuperscript(): void
    {
        $bbcode = 'E=mc[sup]2[/sup]';
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        $this->assertSame('E=mc<sup>2</sup>', $html);
    }

    public function testSubscript(): void
    {
        $bbcode = 'H[sub]2[/sub]O';
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        $this->assertSame('H<sub>2</sub>O', $html);
    }

    public function testHorizontalRule(): void
    {
        $bbcode = 'Before[hr]After';
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('Before', $html);
        $this->assertStringContainsString('<hr />', $html);
        $this->assertStringContainsString('After', $html);
    }

    public function testEmailWithAddress(): void
    {
        $bbcode = '[email=user@example.com]Contact me[/email]';
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        $this->assertSame('<a href="mailto:user@example.com">Contact me</a>', $html);
    }

    public function testEmailFromContent(): void
    {
        $bbcode = '[email]user@example.com[/email]';
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        $this->assertSame('<a href="mailto:user@example.com">user@example.com</a>', $html);
    }

    public function testInvalidEmail(): void
    {
        $bbcode = '[email]not-an-email[/email]';
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        // Should render as plain text when email is invalid
        $this->assertSame('not-an-email', $html);
        $this->assertStringNotContainsString('<a href', $html);
    }

    public function testYoutubeEmbed(): void
    {
        $bbcode = '[youtube]dQw4w9WgXcQ[/youtube]';
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<iframe', $html);
        $this->assertStringContainsString('https://www.youtube.com/embed/dQw4w9WgXcQ', $html);
        $this->assertStringContainsString('allowfullscreen', $html);
    }

    public function testInvalidYoutubeId(): void
    {
        $bbcode = '[youtube]invalid-id[/youtube]';
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        // Should render nothing for invalid video ID
        $this->assertSame('', $html);
    }

    public function testCombinedNewTags(): void
    {
        $bbcode = 'Text with [s]strikethrough[/s] and [sup]superscript[/sup]';
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<del>strikethrough</del>', $html);
        $this->assertStringContainsString('<sup>superscript</sup>', $html);
    }

    public function testCenterAlignment(): void
    {
        $bbcode = '[center]Centered text[/center]';
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        $this->assertSame('<div align="center">Centered text</div>', $html);
    }

    public function testLeftAlignment(): void
    {
        $bbcode = '[left]Left-aligned text[/left]';
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        $this->assertSame('<div align="left">Left-aligned text</div>', $html);
    }

    public function testRightAlignment(): void
    {
        $bbcode = '[right]Right-aligned text[/right]';
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        $this->assertSame('<div align="right">Right-aligned text</div>', $html);
    }

    public function testJustifyAlignment(): void
    {
        $bbcode = '[justify]Justified text[/justify]';
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        $this->assertSame('<div align="justify">Justified text</div>', $html);
    }

    public function testAlignmentWithFormatting(): void
    {
        $bbcode = '[center][b]Bold centered[/b][/center]';
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        $this->assertSame('<div align="center"><strong>Bold centered</strong></div>', $html);
    }

    public function testImageWithAltText(): void
    {
        $bbcode = '[img alt="A beautiful sunset"]http://example.com/sunset.jpg[/img]';
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        $this->assertSame('<img src="http://example.com/sunset.jpg" alt="A beautiful sunset" />', $html);
    }

    public function testImageWithoutAltText(): void
    {
        $bbcode = '[img]http://example.com/image.png[/img]';
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        // Should have empty alt attribute
        $this->assertSame('<img src="http://example.com/image.png" alt="" />', $html);
    }

    public function testImageAltTextEscaping(): void
    {
        // Test with raw quotes and tags (what users actually write)
        $bbcode = '[img alt=\'Test "quotes" and <tags>\']http://example.com/img.png[/img]';
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        // Quotes and tags should be properly escaped in HTML
        $this->assertStringContainsString('alt="Test &quot;quotes&quot; and &lt;tags&gt;"', $html);
    }

    public function testCodeWithLanguage(): void
    {
        $bbcode = '[code=php]echo "Hello World";[/code]';
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        $this->assertStringContainsString('<pre><code class="language-php">', $html);
        $this->assertStringContainsString('echo', $html);
        $this->assertStringContainsString('</code></pre>', $html);
    }

    public function testCodeWithoutLanguage(): void
    {
        $bbcode = '[code]generic code[/code]';
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        // Should not have language class
        $this->assertStringContainsString('<pre><code>', $html);
        $this->assertStringNotContainsString('class=', $html);
        $this->assertStringContainsString('generic code', $html);
    }

    public function testCodeLanguageVariations(): void
    {
        $languages = ['javascript', 'python', 'java', 'cpp', 'csharp', 'html', 'css'];

        foreach ($languages as $lang) {
            $bbcode = "[code=$lang]code[/code]";
            $doc = $this->parser->parse($bbcode);
            $html = $this->renderer->render($doc);

            $this->assertStringContainsString('class="language-' . $lang . '"', $html);
        }
    }

    public function testCodeLanguageInvalidCharacters(): void
    {
        // Language with invalid characters should be ignored
        $bbcode = '[code=php<script>]code[/code]';
        $doc = $this->parser->parse($bbcode);
        $html = $this->renderer->render($doc);

        // Should not have language class (invalid)
        $this->assertStringNotContainsString('class=', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }
}
