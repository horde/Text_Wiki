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
 * Integration test: Tiki Array AST → Adapter → Typed AST → Xhtml Renderer
 *
 * Tests that old Tiki-style array AST works with new renderer via adapter.
 * This demonstrates backward compatibility for existing engines.
 * @coversNothing
 */
class TikiAdapterIntegrationTest extends TestCase
{
    private ArrayToTypedAst $adapter;
    private Xhtml $renderer;

    protected function setUp(): void
    {
        $this->adapter = new ArrayToTypedAst();
        $this->renderer = new Xhtml();
    }

    public function testTikiStyleBold(): void
    {
        // Tiki might use similar format to Cowiki
        $arrayAst = [
            ['type' => 'b', 'text' => 'bold text'],
        ];

        $typedAst = $this->adapter->convert($arrayAst);
        $html = $this->renderer->render($typedAst);

        $this->assertSame('<strong>bold text</strong>', $html);
    }

    public function testTikiStyleHeading(): void
    {
        // Test heading-like structure
        $arrayAst = [
            ['type' => 'b', 'children' => [
                'Heading Text',
            ]],
        ];

        $typedAst = $this->adapter->convert($arrayAst);
        $html = $this->renderer->render($typedAst);

        $this->assertStringContainsString('<strong>', $html);
        $this->assertStringContainsString('Heading Text', $html);
        $this->assertStringContainsString('</strong>', $html);
    }

    public function testTikiStyleLink(): void
    {
        $arrayAst = [
            ['type' => 'url', 'attr' => ['href' => 'http://tiki.org'], 'text' => 'Tiki Wiki'],
        ];

        $typedAst = $this->adapter->convert($arrayAst);
        $html = $this->renderer->render($typedAst);

        $this->assertSame('<a href="http://tiki.org">Tiki Wiki</a>', $html);
    }

    public function testTikiComplexStructure(): void
    {
        // More complex nested structure typical of Tiki
        $arrayAst = [
            ['type' => 'text', 'text' => 'Start '],
            ['type' => 'b', 'children' => [
                ['type' => 'text', 'text' => 'bold '],
                ['type' => 'i', 'children' => [
                    ['type' => 'text', 'text' => 'italic'],
                ]],
                ['type' => 'text', 'text' => ' bold'],
            ]],
            ['type' => 'text', 'text' => ' end'],
        ];

        $typedAst = $this->adapter->convert($arrayAst);
        $html = $this->renderer->render($typedAst);

        $this->assertStringContainsString('Start ', $html);
        $this->assertStringContainsString('<strong>bold <em>italic</em> bold</strong>', $html);
        $this->assertStringContainsString(' end', $html);
    }

    public function testTikiList(): void
    {
        $arrayAst = [
            ['type' => 'list', 'children' => [
                ['type' => '*', 'children' => [
                    ['type' => 'text', 'text' => 'First item'],
                ]],
                ['type' => '*', 'children' => [
                    ['type' => 'text', 'text' => 'Second item'],
                ]],
            ]],
        ];

        $typedAst = $this->adapter->convert($arrayAst);
        $html = $this->renderer->render($typedAst);

        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('First item', $html);
        $this->assertStringContainsString('Second item', $html);
        $this->assertStringContainsString('</ul>', $html);
    }

    public function testTikiMixedContent(): void
    {
        // Paragraph with mixed inline formatting
        $arrayAst = [
            'Normal text with ',
            ['type' => 'b', 'text' => 'bold'],
            ' and ',
            ['type' => 'i', 'text' => 'italic'],
            ' formatting.',
        ];

        $typedAst = $this->adapter->convert($arrayAst);
        $html = $this->renderer->render($typedAst);

        $this->assertStringContainsString('Normal text with', $html);
        $this->assertStringContainsString('<strong>bold</strong>', $html);
        $this->assertStringContainsString('and', $html);
        $this->assertStringContainsString('<em>italic</em>', $html);
        $this->assertStringContainsString('formatting.', $html);
    }

    public function testTikiEmptyContent(): void
    {
        $arrayAst = [
            ['type' => 'b', 'children' => []],
        ];

        $typedAst = $this->adapter->convert($arrayAst);
        $html = $this->renderer->render($typedAst);

        $this->assertSame('<strong></strong>', $html);
    }

    public function testTikiCodeBlock(): void
    {
        $arrayAst = [
            ['type' => 'code', 'text' => 'function test() { return true; }'],
        ];

        $typedAst = $this->adapter->convert($arrayAst);
        $html = $this->renderer->render($typedAst);

        $this->assertStringContainsString('<pre><code>', $html);
        $this->assertStringContainsString('function test()', $html);
        $this->assertStringContainsString('</code></pre>', $html);
    }
}
