<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Tiki\Tag;

use Horde\Text\Wiki\AbstractTagDefinition;
use Horde\Text\Wiki\BBCode\Validator\UrlAttributeValidator;
use Horde\Text\Wiki\TagType;

/**
 * URL tag definition for Tiki
 *
 * Tiki URL syntax: [url|text] or inline http://...
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class UrlTag extends AbstractTagDefinition
{
    private UrlAttributeValidator $validator;

    public function __construct()
    {
        parent::__construct('url', TagType::INLINE);
        $this->validator = new UrlAttributeValidator();
    }

    public function validateAttributes(array $attrs): bool
    {
        if (!isset($attrs['href'])) {
            return true;
        }

        return $this->validator->validate($attrs['href']);
    }
}
