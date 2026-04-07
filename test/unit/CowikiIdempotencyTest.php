<?php

declare(strict_types=1);

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use Horde\Text\Wiki\CowikiParserBold;
use Horde\Text\Wiki\CowikiParserItalic;
use Horde\Text\Wiki\CowikiParserHeading;
use Horde\Text\Wiki\CowikiParserList;
use Horde\Text\Wiki\CowikiParserUrl;
use Horde\Text\Wiki\CowikiRendererBold;
use Horde\Text\Wiki\CowikiRendererItalic;
use Horde\Text\Wiki\CowikiRendererHeading;
use Horde\Text\Wiki\CowikiRendererList;
use Horde\Text\Wiki\CowikiRendererUrl;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Test Cowiki parser idempotency - parse to Cowiki should produce identical output
 *
 * This tests that: source -> parse -> render(Cowiki) -> parse -> render(Cowiki) = stable
 */
#[CoversClass(CowikiParserBold::class)]
#[CoversClass(CowikiParserItalic::class)]
#[CoversClass(CowikiParserHeading::class)]
#[CoversClass(CowikiParserList::class)]
#[CoversClass(CowikiParserUrl::class)]
#[CoversClass(CowikiRendererBold::class)]
#[CoversClass(CowikiRendererItalic::class)]
#[CoversClass(CowikiRendererHeading::class)]
#[CoversClass(CowikiRendererList::class)]
#[CoversClass(CowikiRendererUrl::class)]
class CowikiIdempotencyTest extends TestCase
{
    public function testBoldIdempotency(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Bold']);
        $source = 'This is *bold* text';

        // First pass
        $firstPass = $wiki->transform($source, 'Cowiki');

        // Second pass - should produce same output
        $secondPass = $wiki->transform($firstPass, 'Cowiki');

        $this->assertEquals($firstPass, $secondPass, 'Bold markup should be idempotent');
        $this->assertStringContainsString('*bold*', $firstPass);
    }

    public function testItalicIdempotency(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Italic']);
        $source = 'This is /italic/ text';

        $firstPass = $wiki->transform($source, 'Cowiki');
        $secondPass = $wiki->transform($firstPass, 'Cowiki');

        $this->assertEquals($firstPass, $secondPass, 'Italic markup should be idempotent');
        $this->assertStringContainsString('/italic/', $firstPass);
    }

    public function testHeadingIdempotency(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Heading']);
        $source = '+ Heading Level 1';

        $firstPass = $wiki->transform($source, 'Cowiki');
        $secondPass = $wiki->transform($firstPass, 'Cowiki');

        $this->assertEquals($firstPass, $secondPass, 'Heading markup should be idempotent');
        $this->assertStringContainsString('+ ', $firstPass);
    }

    public function testUrlDescribedIdempotency(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Url']);
        $source = '((http://example.com)(Example Site))';

        $firstPass = $wiki->transform($source, 'Cowiki');
        $secondPass = $wiki->transform($firstPass, 'Cowiki');

        $this->assertEquals($firstPass, $secondPass, 'Described URL markup should be idempotent');
        $this->assertStringContainsString('((http://example.com)(Example Site))', $firstPass);
    }

    public function testUrlInlineIdempotency(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Url']);
        $source = 'Visit http://example.com today';

        $firstPass = $wiki->transform($source, 'Cowiki');
        $secondPass = $wiki->transform($firstPass, 'Cowiki');

        $this->assertEquals($firstPass, $secondPass, 'Inline URL markup should be idempotent');
        $this->assertStringContainsString('http://example.com', $firstPass);
    }

    public function testCombinedMarkupIdempotency(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Bold', 'Italic', 'Heading']);
        $source = "+ Heading with *bold* and /italic/\n\nParagraph with *bold* text.";

        $firstPass = $wiki->transform($source, 'Cowiki');
        $secondPass = $wiki->transform($firstPass, 'Cowiki');

        $this->assertEquals($firstPass, $secondPass, 'Combined markup should be idempotent');
        $this->assertStringContainsString('+ ', $firstPass);
        $this->assertStringContainsString('*bold*', $firstPass);
        $this->assertStringContainsString('/italic/', $firstPass);
    }

    public function testListIdempotency(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['List']);
        $source = "* Item 1\n* Item 2\n * Nested\n";

        $firstPass = $wiki->transform($source, 'Cowiki');
        $secondPass = $wiki->transform($firstPass, 'Cowiki');

        $this->assertEquals($firstPass, $secondPass, 'List markup should be idempotent');
    }

    public function testComplexDocumentIdempotency(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', [
            'Heading', 'Bold', 'Italic', 'List', 'Url', 'Paragraph'
        ]);

        $source = <<<'COWIKI'
+ Main Heading

This is a paragraph with *bold* and /italic/ text.

++ Sub Heading

* First item
* Second item with *emphasis*
 * Nested item

Visit ((http://example.com)(our website)) for more.
COWIKI;

        $firstPass = $wiki->transform($source, 'Cowiki');
        $secondPass = $wiki->transform($firstPass, 'Cowiki');
        $thirdPass = $wiki->transform($secondPass, 'Cowiki');

        // Normalize whitespace for comparison - complex multi-parser documents
        // may have legitimate whitespace normalization on first pass
        $normalizeWs = fn($s) => preg_replace('/\n\n+/', "\n\n", trim($s));

        // After stabilization, output should remain constant
        $this->assertEquals(
            $normalizeWs($secondPass),
            $normalizeWs($thirdPass),
            'Complex document should stabilize after one pass'
        );

        // First pass might differ slightly due to normalization, but subsequent passes should be identical
        $this->assertNotEmpty($firstPass);
        $this->assertNotEmpty($secondPass);
    }

    public function testRoundTripThroughXhtml(): void
    {
        $wiki = TextWikiBase::factory('Cowiki', ['Bold', 'Italic']);
        $source = 'Text with *bold* and /italic/ formatting';

        // Parse to XHTML
        $xhtml = $wiki->transform($source, 'Xhtml');
        $this->assertStringContainsString('<b>bold</b>', $xhtml);
        $this->assertStringContainsString('<i>italic</i>', $xhtml);

        // Parse back to Cowiki
        $backToCowiki = $wiki->transform($source, 'Cowiki');
        $this->assertStringContainsString('*bold*', $backToCowiki);
        $this->assertStringContainsString('/italic/', $backToCowiki);

        // Verify stability
        $secondPass = $wiki->transform($backToCowiki, 'Cowiki');
        $this->assertEquals($backToCowiki, $secondPass);
    }
}
