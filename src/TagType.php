<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki;

/**
 * Tag types for nesting validation
 *
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
enum TagType: string
{
    /** Inline element (bold, italic, url, etc.) */
    case INLINE = 'inline';

    /** Block-level element (code, quote, list, etc.) */
    case BLOCK = 'block';

    /** Can be either inline or block (rare) */
    case MIXED = 'mixed';
}
