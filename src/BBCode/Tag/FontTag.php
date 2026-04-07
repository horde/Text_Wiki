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
use Horde\Text\Wiki\BBCode\Validator\FontAttributeValidator;
use Horde\Text\Wiki\TagType;

/**
 * [font=Arial]text[/font] tag definition
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class FontTag extends AbstractTagDefinition
{
    private FontAttributeValidator $validator;

    public function __construct()
    {
        parent::__construct('font', TagType::INLINE);
        $this->validator = new FontAttributeValidator();
    }

    /**
     * Validate font attribute
     *
     * Checks 'font' attribute for CSS injection vectors.
     *
     * @param array $attrs Attributes from token
     *
     * @return bool True if font is safe
     */
    public function validateAttributes(array $attrs): bool
    {
        if (!isset($attrs['font'])) {
            return false; // [font] requires a font attribute
        }

        return $this->validator->validate($attrs['font']);
    }
}
