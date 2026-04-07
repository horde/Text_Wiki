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
use Horde\Text\Wiki\Renderer\BBCode;
use PHPUnit\Framework\TestCase;

/**
 * BBCode round-trip idempotency test
 *
 * Verifies: parse → render → parse → render stabilizes.
 * Uses whitespace normalization (collapse multiple blank lines).
 *
 * @coversNothing
 */
class BBCodeIdempotencyTest extends TestCase
{
    private BBCodeParser $parser;
    private BBCode $renderer;

    protected function setUp(): void
    {
        $this->parser = new BBCodeParser();
        $this->renderer = new BBCode();
    }

    private function roundTrip(string $input): string
    {
        return $this->renderer->render($this->parser->parse($input));
    }

    private function normalizeWs(string $s): string
    {
        return preg_replace('/\n\n+/', "\n\n", trim($s));
    }

    /**
     * Assert idempotency: second pass output equals first pass output
     */
    private function assertIdempotent(string $source, string $message = ''): void
    {
        $firstPass = $this->roundTrip($source);
        $secondPass = $this->roundTrip($firstPass);

        $this->assertEquals(
            $this->normalizeWs($firstPass),
            $this->normalizeWs($secondPass),
            $message ?: 'Output should stabilize after one pass'
        );
    }

    // ---------------------------------------------------------------
    // Inline formatting
    // ---------------------------------------------------------------

    public function testBoldIdempotency(): void
    {
        $this->assertIdempotent('[b]bold text[/b]', 'Bold');
    }

    public function testItalicIdempotency(): void
    {
        $this->assertIdempotent('[i]italic text[/i]', 'Italic');
    }

    public function testUnderlineIdempotency(): void
    {
        $this->assertIdempotent('[u]underlined[/u]', 'Underline');
    }

    public function testStrikeIdempotency(): void
    {
        $this->assertIdempotent('[s]struck[/s]', 'Strike');
    }

    public function testSuperscriptIdempotency(): void
    {
        $this->assertIdempotent('E=mc[sup]2[/sup]', 'Superscript');
    }

    public function testSubscriptIdempotency(): void
    {
        $this->assertIdempotent('H[sub]2[/sub]O', 'Subscript');
    }

    // ---------------------------------------------------------------
    // Links and media
    // ---------------------------------------------------------------

    public function testUrlWithHrefIdempotency(): void
    {
        $this->assertIdempotent('[url=http://example.com]Example Site[/url]', 'URL with href');
    }

    public function testUrlBareIdempotency(): void
    {
        $this->assertIdempotent('[url]http://example.com[/url]', 'Bare URL');
    }

    public function testEmailWithAttrIdempotency(): void
    {
        $this->assertIdempotent('[email=user@example.com]Contact Us[/email]', 'Email with attr');
    }

    public function testEmailBareIdempotency(): void
    {
        $this->assertIdempotent('[email]user@example.com[/email]', 'Bare email');
    }

    public function testImageIdempotency(): void
    {
        $this->assertIdempotent('[img]http://example.com/img.png[/img]', 'Image');
    }

    public function testYoutubeIdempotency(): void
    {
        $this->assertIdempotent('[youtube]dQw4w9WgXcQ[/youtube]', 'YouTube');
    }

    // ---------------------------------------------------------------
    // Block elements
    // ---------------------------------------------------------------

    public function testBlockquoteIdempotency(): void
    {
        $this->assertIdempotent('[quote]quoted text[/quote]', 'Blockquote');
    }

    public function testBlockquoteWithAuthorIdempotency(): void
    {
        $this->assertIdempotent('[quote=Author]quoted text[/quote]', 'Blockquote with author');
    }

    public function testCodeIdempotency(): void
    {
        $this->assertIdempotent('[code]echo "hello";[/code]', 'Code');
    }

    public function testCodeWithLanguageIdempotency(): void
    {
        $this->assertIdempotent('[code=php]echo "hello";[/code]', 'Code with language');
    }

    public function testHorizIdempotency(): void
    {
        $this->assertIdempotent('[hr]', 'Horizontal rule');
    }

    // ---------------------------------------------------------------
    // Alignment
    // ---------------------------------------------------------------

    public function testCenterIdempotency(): void
    {
        $this->assertIdempotent('[center]centered text[/center]', 'Center');
    }

    public function testLeftIdempotency(): void
    {
        $this->assertIdempotent('[left]left text[/left]', 'Left');
    }

    public function testRightIdempotency(): void
    {
        $this->assertIdempotent('[right]right text[/right]', 'Right');
    }

    public function testJustifyIdempotency(): void
    {
        $this->assertIdempotent('[justify]justified text[/justify]', 'Justify');
    }

    // ---------------------------------------------------------------
    // Lists
    // ---------------------------------------------------------------

    public function testBulletListIdempotency(): void
    {
        $this->assertIdempotent("[list]\n[*]Item A\n[*]Item B\n[/list]", 'Bullet list');
    }

    public function testOrderedListIdempotency(): void
    {
        $this->assertIdempotent("[list=1]\n[*]First\n[*]Second\n[/list]", 'Ordered list');
    }

    public function testAlphaListIdempotency(): void
    {
        $this->assertIdempotent("[list=a]\n[*]Alpha\n[*]Beta\n[/list]", 'Alpha list');
    }

    public function testNestedListIdempotency(): void
    {
        $this->assertIdempotent(
            "[list]\n[*]Outer\n[list]\n[*]Inner\n[/list]\n[*]Back\n[/list]",
            'Nested list'
        );
    }

    // ---------------------------------------------------------------
    // Styling
    // ---------------------------------------------------------------

    public function testColorIdempotency(): void
    {
        $this->assertIdempotent('[color=red]colored text[/color]', 'Color');
    }

    public function testFontIdempotency(): void
    {
        $this->assertIdempotent('[font=Arial]styled text[/font]', 'Font');
    }

    public function testSizeIdempotency(): void
    {
        $this->assertIdempotent('[size=14]sized text[/size]', 'Size');
    }

    // ---------------------------------------------------------------
    // Mixed / combined
    // ---------------------------------------------------------------

    public function testMixedInlineIdempotency(): void
    {
        $this->assertIdempotent(
            '[b]bold[/b] and [i]italic[/i] text',
            'Mixed inline'
        );
    }

    public function testNestedInlineIdempotency(): void
    {
        $this->assertIdempotent(
            '[b]bold [i]and italic[/i] text[/b]',
            'Nested inline'
        );
    }

    public function testComplexDocumentStabilizes(): void
    {
        $source = <<<'BBCODE'
[b]Bold text[/b] and [i]italic[/i]

[quote=Author]A famous quote[/quote]

[list]
[*]Item 1
[*]Item 2
[list=1]
[*]Nested ordered
[/list]
[/list]

[code=php]echo "hello";[/code]

[hr]

[url=http://example.com]Visit us[/url]

[color=red]red text[/color]

[center]centered[/center]

[img]http://example.com/img.png[/img]
BBCODE;

        $firstPass = $this->roundTrip($source);
        $secondPass = $this->roundTrip($firstPass);
        $thirdPass = $this->roundTrip($secondPass);

        $this->assertEquals(
            $this->normalizeWs($secondPass),
            $this->normalizeWs($thirdPass),
            'Complex document should stabilize after one pass'
        );

        $this->assertNotEmpty($firstPass);
        $this->assertNotEmpty($secondPass);
    }

    public function testGetFormat(): void
    {
        $this->assertSame('bbcode', $this->renderer->getFormat());
    }
}
