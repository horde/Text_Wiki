<?php

declare(strict_types=1);

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\CowikiEngine;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Test Cowiki list parsing (* for bullets, # for numbers)
 */
#[CoversClass(CowikiEngine::class)]
class CowikiParseListTest extends TestCase
{
    private CowikiEngine $wiki;

    protected function setUp(): void
    {
        $this->wiki = new CowikiEngine();
    }

    public function testSimpleBulletList(): void
    {
        $source = "* Item 1\n* Item 2\n* Item 3\n";
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<ul>', $result);
        $this->assertStringContainsString('<li>', $result);
        $this->assertStringContainsString('Item 1', $result);
        $this->assertStringContainsString('Item 2', $result);
        $this->assertStringContainsString('Item 3', $result);
        $this->assertStringContainsString('</ul>', $result);
    }

    public function testSimpleNumberedList(): void
    {
        $source = "# Item 1\n# Item 2\n# Item 3\n";
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<ol>', $result);
        $this->assertStringContainsString('<li>', $result);
        $this->assertStringContainsString('Item 1', $result);
        $this->assertStringContainsString('Item 2', $result);
        $this->assertStringContainsString('Item 3', $result);
        $this->assertStringContainsString('</ol>', $result);
    }

    public function testNestedBulletList(): void
    {
        $source = "* Item 1\n * Nested Item 1.1\n * Nested Item 1.2\n* Item 2\n";
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<ul>', $result);
        $this->assertStringContainsString('Item 1', $result);
        $this->assertStringContainsString('Nested Item 1.1', $result);
        $this->assertStringContainsString('Nested Item 1.2', $result);
        $this->assertStringContainsString('Item 2', $result);

        // Should have nested <ul> tags
        $ulCount = substr_count($result, '<ul>');
        $this->assertGreaterThan(1, $ulCount, 'Should have nested unordered lists');
    }

    public function testNestedNumberedList(): void
    {
        $source = "# Item 1\n # Nested Item 1.1\n # Nested Item 1.2\n# Item 2\n";
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<ol>', $result);
        $this->assertStringContainsString('Item 1', $result);
        $this->assertStringContainsString('Nested Item 1.1', $result);
        $this->assertStringContainsString('Nested Item 1.2', $result);
        $this->assertStringContainsString('Item 2', $result);

        // Should have nested <ol> tags
        $olCount = substr_count($result, '<ol>');
        $this->assertGreaterThan(1, $olCount, 'Should have nested ordered lists');
    }

    public function testMixedList(): void
    {
        $source = "* Bullet item\n # Numbered sub-item\n # Another numbered\n* Another bullet\n";
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<ul>', $result);
        $this->assertStringContainsString('<ol>', $result);
        $this->assertStringContainsString('Bullet item', $result);
        $this->assertStringContainsString('Numbered sub-item', $result);
        $this->assertStringContainsString('Another bullet', $result);
    }

    public function testDeepNesting(): void
    {
        $source = "* Level 1\n * Level 2\n  * Level 3\n   * Level 4\n";
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('Level 1', $result);
        $this->assertStringContainsString('Level 2', $result);
        $this->assertStringContainsString('Level 3', $result);
        $this->assertStringContainsString('Level 4', $result);

        // Should have multiple nested ul tags
        $ulCount = substr_count($result, '<ul>');
        $this->assertGreaterThanOrEqual(2, $ulCount, 'Should have deeply nested lists');
    }

    public function testListWithFormatting(): void
    {
        $source = "* Item with *bold*\n* Item with /italic/\n";
        $result = $this->wiki->transform($source, 'Xhtml');

        $this->assertStringContainsString('<ul>', $result);
        $this->assertStringContainsString('<strong>bold</strong>', $result);
        $this->assertStringContainsString('<em>italic</em>', $result);
    }

    public function testListSeparatedByBlankLine(): void
    {
        $source = "* Item 1\n* Item 2\n\n* Item 3\n* Item 4\n";
        $result = $this->wiki->transform($source, 'Xhtml');

        // Blank line may create separate lists or single list depending on parser
        $this->assertStringContainsString('Item 1', $result);
        $this->assertStringContainsString('Item 2', $result);
        $this->assertStringContainsString('Item 3', $result);
        $this->assertStringContainsString('Item 4', $result);
    }

    public function testListRenderToPlain(): void
    {
        $source = "* Item 1\n* Item 2\n# Numbered 1\n";
        $result = $this->wiki->transform($source, 'Plain');

        // Plain should preserve content without HTML
        $this->assertStringContainsString('Item 1', $result);
        $this->assertStringContainsString('Item 2', $result);
        $this->assertStringContainsString('Numbered 1', $result);
        $this->assertStringNotContainsString('<ul>', $result);
        $this->assertStringNotContainsString('<ol>', $result);
    }

    public function testEmptyListItem(): void
    {
        $source = "* Item 1\n* \n* Item 3\n";
        $result = $this->wiki->transform($source, 'Xhtml');

        // Should handle empty list items
        $this->assertStringContainsString('Item 1', $result);
        $this->assertStringContainsString('Item 3', $result);
    }
}
