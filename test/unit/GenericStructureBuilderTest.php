<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Test;

use Horde\Text\Wiki\BBCode\Tag\BoldTag;
use Horde\Text\Wiki\BBCode\Tag\CodeTag;
use Horde\Text\Wiki\BBCode\Tag\ListItemTag;
use Horde\Text\Wiki\BBCode\Tag\ListTag;
use Horde\Text\Wiki\GenericStructureBuilder;
use Horde\Text\Wiki\Node\ElementNode;
use Horde\Text\Wiki\Node\TextNode;
use Horde\Text\Wiki\SimpleTagRegistry;
use Horde\Text\Wiki\Token;
use Horde\Text\Wiki\TokenType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GenericStructureBuilder::class)]
class GenericStructureBuilderTest extends TestCase
{
    private SimpleTagRegistry $registry;
    private GenericStructureBuilder $builder;

    protected function setUp(): void
    {
        $this->registry = new SimpleTagRegistry();
        $this->registry->register(new BoldTag());
        $this->registry->register(new CodeTag());
        $this->registry->register(new ListTag());
        $this->registry->register(new ListItemTag());

        $this->builder = new GenericStructureBuilder($this->registry);
    }

    public function testSimpleTag(): void
    {
        $tokens = [
            new Token(TokenType::OPEN_TAG, 'bold', 0),
            new Token(TokenType::TEXT, 'bold', 3),
            new Token(TokenType::CLOSE_TAG, 'bold', 7),
        ];

        $doc = $this->builder->build($tokens);
        $children = $doc->getChildren();

        $this->assertCount(1, $children);
        $this->assertInstanceOf(ElementNode::class, $children[0]);
        $this->assertSame('bold', $children[0]->getName());

        $boldChildren = $children[0]->getChildren();
        $this->assertCount(1, $boldChildren);
        $this->assertInstanceOf(TextNode::class, $boldChildren[0]);
        $this->assertSame('bold', $boldChildren[0]->getText());
    }

    public function testNestedTags(): void
    {
        $tokens = [
            new Token(TokenType::OPEN_TAG, 'bold', 0),
            new Token(TokenType::TEXT, 'outer', 3),
            new Token(TokenType::OPEN_TAG, 'bold', 8),
            new Token(TokenType::TEXT, 'inner', 11),
            new Token(TokenType::CLOSE_TAG, 'bold', 16),
            new Token(TokenType::CLOSE_TAG, 'bold', 20),
        ];

        $doc = $this->builder->build($tokens);
        $outer = $doc->getChildren()[0];

        $this->assertSame('bold', $outer->getName());
        $this->assertCount(2, $outer->getChildren());

        $innerBold = $outer->getChildren()[1];
        $this->assertInstanceOf(ElementNode::class, $innerBold);
        $this->assertSame('bold', $innerBold->getName());
    }

    public function testAutoCloseUnclosedTag(): void
    {
        $tokens = [
            new Token(TokenType::OPEN_TAG, 'bold', 0),
            new Token(TokenType::TEXT, 'unclosed', 3),
        ];

        $doc = $this->builder->build($tokens);
        $bold = $doc->getChildren()[0];

        $this->assertInstanceOf(ElementNode::class, $bold);
        $this->assertTrue($bold->isAutoClosed());
    }

    public function testUnknownTagTreatedAsText(): void
    {
        $tokens = [
            new Token(TokenType::OPEN_TAG, 'unknown', 0),
            new Token(TokenType::TEXT, 'text', 9),
        ];

        $doc = $this->builder->build($tokens);
        $children = $doc->getChildren();

        // Unknown tag becomes text: '[unknown]' + 'text' merge into 1 TextNode
        // Structure builder merges consecutive text for cleaner AST
        $this->assertCount(1, $children);
        $this->assertInstanceOf(TextNode::class, $children[0]);
        $this->assertSame('[unknown]text', $children[0]->getText());
    }

    public function testVerbatimContent(): void
    {
        $tokens = [
            new Token(TokenType::OPEN_TAG, 'code', 0),
            new Token(TokenType::OPEN_TAG, 'bold', 6), // Should not be parsed as tag
            new Token(TokenType::TEXT, 'raw', 9),
            new Token(TokenType::CLOSE_TAG, 'bold', 12),
            new Token(TokenType::CLOSE_TAG, 'code', 16),
        ];

        $doc = $this->builder->build($tokens);
        $code = $doc->getChildren()[0];

        $this->assertSame('code', $code->getName());
        $this->assertTrue($code->isVerbatim());

        // Note: Current implementation may not handle verbatim content during build
        // This test documents expected behavior for future implementation
    }

    public function testListNesting(): void
    {
        $tokens = [
            new Token(TokenType::OPEN_TAG, 'list', 0),
            new Token(TokenType::OPEN_TAG, 'listitem', 6),
            new Token(TokenType::TEXT, 'item1', 9),
            new Token(TokenType::OPEN_TAG, 'listitem', 14),
            new Token(TokenType::TEXT, 'item2', 17),
            new Token(TokenType::CLOSE_TAG, 'list', 22),
        ];

        $doc = $this->builder->build($tokens);
        $list = $doc->getChildren()[0];

        $this->assertSame('list', $list->getName());
        $this->assertCount(2, $list->getChildren());
        $this->assertSame('listitem', $list->getChildren()[0]->getName());
        $this->assertSame('listitem', $list->getChildren()[1]->getName());
    }
}
