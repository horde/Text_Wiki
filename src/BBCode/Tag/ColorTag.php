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
use Horde\Text\Wiki\BBCode\Validator\ColorAttributeValidator;
use Horde\Text\Wiki\TagType;

/**
 * [color=#RGB]text[/color] tag definition
 *
 * @author   Bertrand Gugger <bertrand@toggg.com>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class ColorTag extends AbstractTagDefinition
{
    private ColorAttributeValidator $validator;

    public function __construct()
    {
        parent::__construct('color', TagType::INLINE);
        $this->validator = new ColorAttributeValidator();
    }

    /**
     * Validate color attribute
     *
     * Checks 'color' attribute for CSS injection vectors.
     *
     * @param array $attrs Attributes from token
     *
     * @return bool True if color is safe
     */
    public function validateAttributes(array $attrs): bool
    {
        if (!isset($attrs['color'])) {
            return false; // [color] requires a color attribute
        }

        return $this->validator->validate($attrs['color']);
    }
}
