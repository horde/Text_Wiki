<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\BBCode\Tag;

use Horde\Text\Wiki\BBCode\AbstractTagDefinition;
use Horde\Text\Wiki\TagType;

/**
 * [size=14]text[/size] tag definition
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class SizeTag extends AbstractTagDefinition
{
    public function __construct()
    {
        parent::__construct('size', TagType::INLINE);
    }

    /**
     * Validate size attribute
     *
     * Checks 'size' attribute is a reasonable number.
     *
     * @param array $attrs Attributes from token
     *
     * @return bool True if size is valid
     */
    public function validateAttributes(array $attrs): bool
    {
        if (!isset($attrs['size'])) {
            return false; // [size] requires a size attribute
        }

        $size = $attrs['size'];

        // Must be numeric
        if (!is_numeric($size)) {
            return false;
        }

        // Reasonable range: 6-48pt
        $sizeInt = (int) $size;
        return $sizeInt >= 6 && $sizeInt <= 48;
    }
}
