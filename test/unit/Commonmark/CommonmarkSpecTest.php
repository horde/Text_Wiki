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
 * Data-driven test runner for the CommonMark spec
 *
 * Parses examples from the CommonMark spec.txt and compares
 * our parser+renderer output against expected HTML.
 */
#[CoversClass(MarkdownParser::class)]
class CommonmarkSpecTest extends TestCase
{
    /**
     * Parse spec.txt and yield test examples
     *
     * @return iterable<string, array{string, string, int}>
     */
    public static function specExamples(): iterable
    {
        $specFile = __DIR__ . '/../../fixtures/commonmark-spec.txt';
        if (!file_exists($specFile)) {
            return;
        }

        $spec = file_get_contents($specFile);
        $section = '';
        $exampleNum = 0;

        // Pattern: ```````````````````````````````` example
        $lines = explode("\n", $spec);
        $inExample = false;
        $markdown = '';
        $html = '';
        $inHtml = false;

        foreach ($lines as $line) {
            // Track section headers
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

                // Normalize: spec uses → for tabs
                $markdown = str_replace('→', "\t", $markdown);
                $html = str_replace('→', "\t", $html);

                // Remove trailing newline from markdown (the last \n before .)
                $markdown = rtrim($markdown, "\n");

                $label = sprintf('Example %d (%s)', $exampleNum, $section);
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
        $parser = new MarkdownParser(MarkdownTagRegistry::commonmark());
        $renderer = new Xhtml();

        $document = $parser->parse($markdown);
        $result = $renderer->render($document);

        $this->assertSame(
            $expectedHtml,
            $result,
            sprintf('CommonMark spec example %d failed', $exampleNum),
        );
    }
}
