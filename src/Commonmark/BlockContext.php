<?php

declare(strict_types=1);

/**
 * Copyright 2025-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Commonmark;

use Horde\Text\Wiki\Node\DocumentNode;
use Horde\Text\Wiki\Node\ElementNode;
use Horde\Text\Wiki\TagRegistry;

/**
 * Block parsing context
 *
 * Maintains the open block stack and document root during block-phase parsing.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class BlockContext
{
    private DocumentNode $document;
    private LinkReferenceMap $refMap;
    private TagRegistry $registry;

    /** @var list<OpenBlock> */
    private array $openBlocks = [];

    public function __construct(TagRegistry $registry)
    {
        $this->document = new DocumentNode();
        $this->refMap = new LinkReferenceMap();
        $this->registry = $registry;
    }

    public function getDocument(): DocumentNode
    {
        return $this->document;
    }

    public function getReferenceMap(): LinkReferenceMap
    {
        return $this->refMap;
    }

    public function getRegistry(): TagRegistry
    {
        return $this->registry;
    }

    /**
     * Push a new open block onto the stack
     */
    public function openBlock(OpenBlock $block): void
    {
        $this->openBlocks[] = $block;
    }

    /**
     * Close the last open block
     */
    public function closeLastBlock(): ?OpenBlock
    {
        return array_pop($this->openBlocks);
    }

    /**
     * @return list<OpenBlock>
     */
    public function getOpenBlocks(): array
    {
        return $this->openBlocks;
    }

    /**
     * Get the tip (innermost open block)
     */
    public function getTip(): ?OpenBlock
    {
        if (empty($this->openBlocks)) {
            return null;
        }
        return $this->openBlocks[array_key_last($this->openBlocks)];
    }

    /**
     * Close all open blocks from the tip back to (and including) the given index
     */
    public function closeBlocksFrom(int $index): void
    {
        while (count($this->openBlocks) > $index) {
            $this->closeLastBlock();
        }
    }

    public function hasGfm(): bool
    {
        return $this->registry->has('table') && $this->registry->has('strike');
    }
}
