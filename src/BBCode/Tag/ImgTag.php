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
use Horde\Text\Wiki\BBCode\Validator\UrlAttributeValidator;
use Horde\Text\Wiki\TagType;

/**
 * [img]http://example.com/image.png[/img] tag definition
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class ImgTag extends AbstractTagDefinition
{
    private UrlAttributeValidator $validator;

    public function __construct()
    {
        parent::__construct('img', TagType::INLINE);
        $this->validator = new UrlAttributeValidator();
    }

    /**
     * Validate image URL from content
     *
     * Note: URL comes from tag content, not attributes.
     * Attributes could contain width/height in future extensions.
     *
     * @param array $attrs Attributes from token
     *
     * @return bool True (validation happens on content, not attributes)
     */
    public function validateAttributes(array $attrs): bool
    {
        // No attribute validation needed - URL comes from content
        return true;
    }
}
