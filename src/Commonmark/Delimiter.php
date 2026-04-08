<?php

declare(strict_types=1);

/**
 * Copyright 2025-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Commonmark;

/**
 * A delimiter entry on the delimiter stack
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class Delimiter
{
    /**
     * @param string $char       The delimiter character ('*', '_', '~')
     * @param int    $numDelims  Current number of delimiters remaining
     * @param int    $origDelims Original number of delimiters
     * @param bool   $canOpen    Whether this can open emphasis
     * @param bool   $canClose   Whether this can close emphasis
     * @param int    $inlineIndex Position in the inlines array
     * @param int    $sourcePos  Position in the source text (for link label extraction)
     */
    public function __construct(
        public readonly string $char,
        public int $numDelims,
        public readonly int $origDelims,
        public bool $canOpen,
        public bool $canClose,
        public int $inlineIndex,
        public readonly int $sourcePos = 0,
    ) {}
}
