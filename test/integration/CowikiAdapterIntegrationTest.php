<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Test\Integration;

use Horde\Text\Wiki\Adapter\ArrayToTypedAst;
use Horde\Text\Wiki\Renderer\Xhtml;
use PHPUnit\Framework\TestCase;

/**
 * Integration test: Cowiki Array AST → Adapter → Typed AST → Xhtml Renderer
 *
 * Tests that old Cowiki-style array AST works with new renderer via adapter.
 * This demonstrates backward compatibility for existing engines.
 */
class CowikiAdapterIntegrationTest extends TestCase
{
    private ArrayToTypedAst $adapter;
    private Xhtml $renderer;

    protected function setUp(): void
    {
        $this->adapter = new ArrayToTypedAst();
        $this->renderer = new Xhtml();
    }

    public function testSimpleArrayAst(): void
    {
        // Simulate Cowiki array format: ['type' => 'tag', 'text' => '...', 'children' => [...]]
        $arrayAst = [
            ['type' => 'b', 'children' => [
                ['type' => 'text', 'text' => 'bold text'],
            ]],
        ];

        $typedAst = $this->adapter->convert($arrayAst);
        $html = $this->renderer->render($typedAst);

        $this->assertSame('<strong>bold text</strong>', $html);
    }

    public function testNestedArrayAst(): void
    {
        $arrayAst = [
            ['type' => 'b', 'children' => [
                ['type' => 'text', 'text' => 'bold '],
                ['type' => 'i', 'children' => [
                    ['type' => 'text', 'text' => 'and italic'],
                ]],
            ]],
        ];

        $typedAst = $this->adapter->convert($arrayAst);
        $html = $this->renderer->render($typedAst);

        $this->assertSame('<strong>bold <em>and italic</em></strong>', $html);
    }

    public function testArrayAstWithAttributes(): void
    {
        $arrayAst = [
            ['type' => 'url', 'attr' => ['href' => 'http://example.com'], 'children' => [
                ['type' => 'text', 'text' => 'link'],
            ]],
        ];

        $typedAst = $this->adapter->convert($arrayAst);
        $html = $this->renderer->render($typedAst);

        $this->assertSame('<a href="http://example.com">link</a>', $html);
    }

    public function testMixedStringAndArrayChildren(): void
    {
        // Some engines might have mixed format
        $arrayAst = [
            'plain text ',
            ['type' => 'b', 'text' => 'bold'],
            ' more text',
        ];

        $typedAst = $this->adapter->convert($arrayAst);
        $html = $this->renderer->render($typedAst);

        $this->assertStringContainsString('plain text', $html);
        $this->assertStringContainsString('<strong>bold</strong>', $html);
        $this->assertStringContainsString('more text', $html);
    }

    public function testAlternativeFieldNames(): void
    {
        // Test 'name' instead of 'type', 'attributes' instead of 'attr'
        $arrayAst = [
            ['name' => 'b', 'attributes' => [], 'content' => [
                'bold text',
            ]],
        ];

        $typedAst = $this->adapter->convert($arrayAst);
        $html = $this->renderer->render($typedAst);

        $this->assertStringContainsString('<strong>bold text</strong>', $html);
    }

    public function testListStructure(): void
    {
        $arrayAst = [
            ['type' => 'list', 'children' => [
                ['type' => '*', 'text' => 'Item 1'],
                ['type' => '*', 'text' => 'Item 2'],
            ]],
        ];

        $typedAst = $this->adapter->convert($arrayAst);
        $html = $this->renderer->render($typedAst);

        $this->assertStringContainsString('<ul>', $html);
        // Note: May fail due to Issue 6 (asterisk rendering)
        $this->assertStringContainsString('Item 1', $html);
        $this->assertStringContainsString('Item 2', $html);
        $this->assertStringContainsString('</ul>', $html);
    }

    public function testPlainStringInput(): void
    {
        // Some engines might just return a string
        $arrayAst = 'plain text content';

        $typedAst = $this->adapter->convert($arrayAst);
        $html = $this->renderer->render($typedAst);

        $this->assertSame('plain text content', $html);
    }

    public function testEmptyArray(): void
    {
        $arrayAst = [];

        $typedAst = $this->adapter->convert($arrayAst);
        $html = $this->renderer->render($typedAst);

        $this->assertSame('', $html);
    }
}
