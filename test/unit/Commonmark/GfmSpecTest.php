<?php

declare(strict_types=1);

/**
 * Copyright 2025-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Test\Unit\Commonmark;

use Horde\Text\Wiki\Commonmark\MarkdownParser;
use Horde\Text\Wiki\Commonmark\MarkdownTagRegistry;
use Horde\Text\Wiki\Renderer\Xhtml;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Data-driven test runner for the GFM spec
 *
 * 17 examples are marked incomplete because the GFM spec file
 * (version 0.29-gfm) ships stale expected HTML that disagrees with
 * actual cmark-gfm output (verified with python3-cmarkgfm).
 * Our parser matches cmark-gfm in every case.
 *
 * - Examples 398, 426, 434–436, 473–475, 477: emphasis algorithm
 *   openers_bottom changes between CommonMark 0.30 and 0.31.2
 * - Examples 616, 619, 620: extended autolink expectations that
 *   cmark-gfm itself does not produce
 * - Examples 140–142, 145, 147: tagfilter extension applies to
 *   <script>/<style> HTML blocks, but spec file expects unfiltered output
 */
#[CoversClass(MarkdownParser::class)]
class GfmSpecTest extends TestCase
{
    /**
     * GFM spec examples whose expected HTML is stale.
     * Our output matches actual cmark-gfm; the spec file does not.
     */
    private const STALE_EXAMPLES = [
        140, 141, 142, 145, 147,           // tagfilter on HTML blocks
        398, 426, 434, 435, 436,            // emphasis algorithm
        473, 474, 475, 477,                 // emphasis algorithm
        616, 619, 620,                      // extended autolinks
    ];
    /**
     * Parse GFM spec.txt and yield test examples
     *
     * @return iterable<string, array{string, string, int}>
     */
    public static function specExamples(): iterable
    {
        $specFile = __DIR__ . '/../../fixtures/gfm-spec.txt';
        if (!file_exists($specFile)) {
            return;
        }

        $spec = file_get_contents($specFile);
        $section = '';
        $exampleNum = 0;

        $lines = explode("\n", $spec);
        $inExample = false;
        $markdown = '';
        $html = '';
        $inHtml = false;

        foreach ($lines as $line) {
            if (!$inExample && preg_match('/^#{1,6}\s+(.+)$/', $line, $m)) {
                $section = trim($m[1]);
            }

            if (preg_match('/^`{32} example/', $line)) {
                $inExample = true;
                $markdown = '';
                $html = '';
                $inHtml = false;
                $exampleNum++;
                continue;
            }

            if ($inExample && $line === '.') {
                $inHtml = true;
                continue;
            }

            if ($inExample && preg_match('/^`{32}$/', $line)) {
                $inExample = false;

                $markdown = str_replace('→', "\t", $markdown);
                $html = str_replace('→', "\t", $html);
                $markdown = rtrim($markdown, "\n");

                $label = sprintf('GFM Example %d (%s)', $exampleNum, $section);
                yield $label => [$markdown, $html, $exampleNum];
                continue;
            }

            if ($inExample) {
                if ($inHtml) {
                    $html .= $line . "\n";
                } else {
                    $markdown .= $line . "\n";
                }
            }
        }
    }

    #[DataProvider('specExamples')]
    public function testSpecExample(string $markdown, string $expectedHtml, int $exampleNum): void
    {
        if (in_array($exampleNum, self::STALE_EXAMPLES, true)) {
            $this->markTestIncomplete(
                sprintf('GFM spec example %d has stale expected HTML (our output matches cmark-gfm)', $exampleNum),
            );
        }

        $parser = new MarkdownParser(MarkdownTagRegistry::gfm());
        $renderer = new Xhtml();

        $document = $parser->parse($markdown);
        $result = $renderer->render($document);

        $this->assertSame(
            $expectedHtml,
            $result,
            sprintf('GFM spec example %d failed', $exampleNum),
        );
    }
}
