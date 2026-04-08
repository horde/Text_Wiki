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
use Horde\Text\Wiki\Node\DocumentNode;
use Horde\Text\Wiki\Node\ElementNode;
use Horde\Text\Wiki\Node\TextNode;
use Horde\Text\Wiki\Parser;
use Horde\Text\Wiki\Renderer\Xhtml;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MarkdownParser
 */
#[CoversClass(MarkdownParser::class)]
class CommonmarkParserTest extends TestCase
{
    private MarkdownParser $parser;

    protected function setUp(): void
    {
        $this->parser = new MarkdownParser();
    }

    public function testImplementsParser(): void
    {
        $this->assertInstanceOf(Parser::class, $this->parser);
    }

    public function testGetFormat(): void
    {
        $this->assertSame('markdown', $this->parser->getFormat());
    }

    public function testParseParagraph(): void
    {
        $doc = $this->parser->parse('Hello, world!');

        $children = $doc->getChildren();
        $this->assertCount(1, $children);
        $this->assertInstanceOf(ElementNode::class, $children[0]);
        $this->assertSame('paragraph', $children[0]->getName());
    }

    public function testParseAtxHeading(): void
    {
        $doc = $this->parser->parse('# Heading 1');

        $children = $doc->getChildren();
        $this->assertCount(1, $children);
        $this->assertInstanceOf(ElementNode::class, $children[0]);
        $this->assertSame('heading', $children[0]->getName());
        $this->assertSame(1, $children[0]->getAttributes()['level']);
    }

    public function testParseThematicBreak(): void
    {
        $doc = $this->parser->parse('---');

        $children = $doc->getChildren();
        $this->assertCount(1, $children);
        $this->assertInstanceOf(ElementNode::class, $children[0]);
        $this->assertSame('horiz', $children[0]->getName());
    }

    public function testParseEmptyDocument(): void
    {
        $doc = $this->parser->parse('');

        $this->assertInstanceOf(DocumentNode::class, $doc);
        $this->assertEmpty($doc->getChildren());
    }

    public function testParseBoldInline(): void
    {
        $doc = $this->parser->parse('**bold**');

        $children = $doc->getChildren();
        $this->assertCount(1, $children);
        $para = $children[0];
        $this->assertSame('paragraph', $para->getName());

        // Look for bold element inside paragraph
        $foundBold = false;
        foreach ($para->getChildren() as $child) {
            if ($child instanceof ElementNode && $child->getName() === 'bold') {
                $foundBold = true;
            }
        }
        $this->assertTrue($foundBold, 'Expected bold element in paragraph');
    }

    public function testParseCodeSpan(): void
    {
        $doc = $this->parser->parse('Use `code` here');

        $children = $doc->getChildren();
        $para = $children[0];

        $foundCodespan = false;
        foreach ($para->getChildren() as $child) {
            if ($child instanceof ElementNode && $child->getName() === 'tt') {
                $foundCodespan = true;
            }
        }
        $this->assertTrue($foundCodespan, 'Expected tt element in paragraph');
    }

    public function testParseFencedCode(): void
    {
        $doc = $this->parser->parse("```php\necho 'hello';\n```");

        $children = $doc->getChildren();
        $this->assertCount(1, $children);
        $this->assertSame('code', $children[0]->getName());
        $this->assertSame('php', $children[0]->getAttributes()['language'] ?? '');
    }

    public function testDefaultsToGfm(): void
    {
        // Default constructor should support GFM features
        $parser = new MarkdownParser();
        $doc = $parser->parse("| A | B |\n| - | - |\n| 1 | 2 |");

        $foundTable = false;
        foreach ($doc->getChildren() as $child) {
            if ($child instanceof ElementNode && $child->getName() === 'table') {
                $foundTable = true;
            }
        }
        $this->assertTrue($foundTable, 'Default parser should support GFM tables');
    }

    public function testCommonmarkModeNoTable(): void
    {
        $parser = new MarkdownParser(MarkdownTagRegistry::commonmark());
        $doc = $parser->parse("| A | B |\n| - | - |\n| 1 | 2 |");

        $foundTable = false;
        foreach ($doc->getChildren() as $child) {
            if ($child instanceof ElementNode && $child->getName() === 'table') {
                $foundTable = true;
            }
        }
        $this->assertFalse($foundTable, 'CommonMark mode should not parse tables');
    }

    public function testXhtmlRenderParagraph(): void
    {
        $renderer = new Xhtml();
        $doc = $this->parser->parse('Hello');
        $result = $renderer->render($doc);

        $this->assertStringContainsString('<p>', $result);
        $this->assertStringContainsString('Hello', $result);
    }
}
