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
use Horde\Text\Wiki\Node\TextNode;

/**
 * Delimiter stack for emphasis/strong algorithm per CommonMark spec
 *
 * Implements the algorithm described in appendix "An algorithm for
 * parsing nested emphasis and links" of the CommonMark spec.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class DelimiterStack
{
    /** @var list<Delimiter> */
    private array $stack = [];

    public function push(Delimiter $delimiter): void
    {
        $this->stack[] = $delimiter;
    }

    /**
     * Remove a delimiter from the stack
     */
    public function remove(Delimiter $delimiter): void
    {
        $index = array_search($delimiter, $this->stack, true);
        if ($index !== false) {
            array_splice($this->stack, $index, 1);
        }
    }

    /**
     * @return list<Delimiter>
     */
    public function getAll(): array
    {
        return $this->stack;
    }

    /**
     * Process emphasis per CommonMark spec algorithm
     *
     * @param list<Node> $inlines The inline node list being built
     * @param Delimiter|null $stackBottom Opaque bottom for link/image processing
     *
     * @return list<Node>
     */
    public function processEmphasis(array &$inlines, ?Delimiter $stackBottom = null): array
    {
        // Find the position of stackBottom
        $bottomIndex = -1;
        if ($stackBottom !== null) {
            $bottomIndex = array_search($stackBottom, $this->stack, true);
            if ($bottomIndex === false) {
                $bottomIndex = -1;
            }
        }

        // current_position starts after stack bottom, scanning for closers
        $openers_bottom = [
            '*' => [1 => $bottomIndex, 2 => $bottomIndex, 3 => $bottomIndex],
            '_' => [1 => $bottomIndex, 2 => $bottomIndex, 3 => $bottomIndex],
            '~' => [1 => $bottomIndex, 2 => $bottomIndex],
        ];

        $currentIdx = $bottomIndex + 1;

        while ($currentIdx < count($this->stack)) {
            $closer = $this->stack[$currentIdx];

            if (!$closer->canClose) {
                $currentIdx++;
                continue;
            }

            $char = $closer->char;
            $closerLen = $closer->numDelims;

            // Look for opener
            $openerFound = false;
            $openerIdx = $currentIdx - 1;

            $useLen = ($closerLen >= 2) ? (($closerLen >= 3) ? 3 : 2) : 1;
            $obKey = min($useLen, 3);
            if ($char === '~') {
                $obKey = min($useLen, 2);
            }

            while ($openerIdx > $bottomIndex) {
                $opener = $this->stack[$openerIdx];

                if ($opener->char === $char && $opener->canOpen) {
                    // "Multiple of 3" rule for * and _
                    if (($char === '*' || $char === '_')
                        && ($opener->canClose || $closer->canOpen)
                        && (($opener->origDelims + $closer->origDelims) % 3 === 0)
                        && ($opener->origDelims % 3 !== 0 || $closer->origDelims % 3 !== 0)
                    ) {
                        $openerIdx--;
                        continue;
                    }

                    $openerFound = true;
                    break;
                }

                if (isset($openers_bottom[$char][$obKey]) && $openerIdx <= $openers_bottom[$char][$obKey]) {
                    break;
                }

                $openerIdx--;
            }

            if (!$openerFound) {
                $openers_bottom[$char][$obKey] = $currentIdx - 1;
                if (!$closer->canOpen) {
                    $this->removeAndConvertToText($closer, $inlines);
                    // Recalculate currentIdx
                    $currentIdx = array_search($closer, $this->stack, true);
                    if ($currentIdx === false) {
                        // It was removed, find next position
                        $currentIdx = $this->findNextIndex($bottomIndex, count($this->stack));
                    } else {
                        $currentIdx++;
                    }
                } else {
                    $currentIdx++;
                }
                continue;
            }

            $opener = $this->stack[$openerIdx];

            // Determine emphasis type
            if ($char === '~') {
                $useLen = 2; // strikethrough always uses 2
            } else {
                if ($closer->numDelims >= 2 && $opener->numDelims >= 2) {
                    $useLen = 2; // strong
                } else {
                    $useLen = 1; // emphasis
                }
            }

            // Build the emphasis/strong/strike node
            $tagName = match (true) {
                $char === '~' => 'strike',
                $useLen === 2 => 'bold',
                default => 'italic',
            };

            $emphNode = new ElementNode($tagName);

            // Move inlines between opener and closer into the new node
            $openerInlineIdx = $opener->inlineIndex;
            $closerInlineIdx = $closer->inlineIndex;

            // Collect nodes between opener and closer
            $innerNodes = [];
            for ($i = $openerInlineIdx + 1; $i < $closerInlineIdx; $i++) {
                if (isset($inlines[$i])) {
                    $innerNodes[] = $inlines[$i];
                }
            }

            foreach ($innerNodes as $child) {
                $emphNode->addChild($child);
            }

            // Remove inner nodes and replace with emphasis node
            array_splice($inlines, $openerInlineIdx + 1, $closerInlineIdx - $openerInlineIdx - 1, [$emphNode]);

            // Update delimiter counts
            $opener->numDelims -= $useLen;
            $closer->numDelims -= $useLen;

            // Update inline indices for all delimiters after the splice
            $removed = $closerInlineIdx - $openerInlineIdx - 2; // nodes removed minus the one added
            foreach ($this->stack as $d) {
                if ($d->inlineIndex > $openerInlineIdx && $d !== $closer) {
                    $d->inlineIndex -= $removed;
                }
            }
            $closer->inlineIndex = $openerInlineIdx + 2;

            // Remove delimiters between opener and closer from stack
            $removeBetween = [];
            foreach ($this->stack as $idx => $d) {
                if ($idx > $openerIdx && $idx < array_search($closer, $this->stack, true)) {
                    $removeBetween[] = $d;
                }
            }
            foreach ($removeBetween as $d) {
                $this->remove($d);
            }

            // Recalculate indices after removal
            $currentIdx = array_search($closer, $this->stack, true);
            if ($currentIdx === false) {
                break;
            }

            // Remove opener/closer if used up
            if ($opener->numDelims === 0) {
                // Remove the text node for opener
                if (isset($inlines[$opener->inlineIndex]) && $inlines[$opener->inlineIndex] instanceof TextNode) {
                    array_splice($inlines, $opener->inlineIndex, 1);
                    foreach ($this->stack as $d) {
                        if ($d->inlineIndex > $opener->inlineIndex) {
                            $d->inlineIndex--;
                        }
                    }
                    $currentIdx = array_search($closer, $this->stack, true);
                }
                $this->remove($opener);
                $currentIdx = array_search($closer, $this->stack, true);
                if ($currentIdx === false) {
                    break;
                }
            } else {
                // Update text node to show remaining delimiters
                if (isset($inlines[$opener->inlineIndex]) && $inlines[$opener->inlineIndex] instanceof TextNode) {
                    $inlines[$opener->inlineIndex] = new TextNode(str_repeat($char, $opener->numDelims));
                }
            }

            if ($closer->numDelims === 0) {
                if (isset($inlines[$closer->inlineIndex]) && $inlines[$closer->inlineIndex] instanceof TextNode) {
                    array_splice($inlines, $closer->inlineIndex, 1);
                    foreach ($this->stack as $d) {
                        if ($d->inlineIndex > $closer->inlineIndex) {
                            $d->inlineIndex--;
                        }
                    }
                }
                $this->remove($closer);
                // currentIdx stays the same (next delimiter is now at this position)
                $currentIdx = array_search($closer, $this->stack, true);
                if ($currentIdx === false) {
                    // Find next valid position
                    $currentIdx = count($this->stack);
                    foreach ($this->stack as $idx => $d) {
                        if ($idx > $bottomIndex && $d->canClose) {
                            $currentIdx = $idx;
                            break;
                        }
                    }
                }
            } else {
                // Update text node to show remaining delimiters
                if (isset($inlines[$closer->inlineIndex]) && $inlines[$closer->inlineIndex] instanceof TextNode) {
                    $inlines[$closer->inlineIndex] = new TextNode(str_repeat($char, $closer->numDelims));
                }
            }
        }

        // Remove remaining delimiters above stackBottom and convert to text
        $toRemove = [];
        foreach ($this->stack as $idx => $d) {
            if ($idx > $bottomIndex) {
                $toRemove[] = $d;
            }
        }
        foreach ($toRemove as $d) {
            $this->remove($d);
        }

        return $inlines;
    }

    private function removeAndConvertToText(Delimiter $delimiter, array &$inlines): void
    {
        // The text node is already in the inlines array, just remove from stack
        $this->remove($delimiter);
    }

    private function findNextIndex(int $bottomIndex, int $max): int
    {
        foreach ($this->stack as $idx => $d) {
            if ($idx > $bottomIndex) {
                return $idx;
            }
        }
        return $max;
    }
}
