<?php

declare(strict_types=1);

/**
 * Copyright 2025-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Commonmark;

use Horde\Text\Wiki\Node\ElementNode;
use Horde\Text\Wiki\Node\Node;

/**
 * Represents an open block during block parsing
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class OpenBlock
{
    /** Accumulated text content for leaf blocks */
    private string $content = '';

    /** Number of content lines appended */
    private int $lineCount = 0;

    /** Whether this block can accept more lines (lazy continuation) */
    private bool $open = true;

    /**
     * @param string      $type   Block type identifier
     * @param ElementNode $node   The AST node for this block
     * @param Node        $parent The parent node this block was added to
     */
    public function __construct(
        public readonly string $type,
        public readonly ElementNode $node,
        public readonly Node $parent,
    ) {}

    public function appendContent(string $line): void
    {
        if ($this->lineCount > 0) {
            $this->content .= "\n";
        }
        $this->content .= $line;
        $this->lineCount++;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function isOpen(): bool
    {
        return $this->open;
    }

    public function close(): void
    {
        $this->open = false;
    }
}
