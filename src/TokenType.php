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
 * Token types produced by tokenizers
 *
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
enum TokenType: string
{
    /** Plain text content between tags */
    case TEXT = 'text';

    /** Opening tag: [b], [url=...] */
    case OPEN_TAG = 'open_tag';

    /** Closing tag: [/b], [/url] */
    case CLOSE_TAG = 'close_tag';

    /** Newline character(s) for block-level awareness */
    case NEWLINE = 'newline';
}
