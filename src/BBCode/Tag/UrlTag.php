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
 * [url]link[/url] and [url=http://...]text[/url] tag definition
 *
 * @author   Ralf Lang <lang@b1-systems.de>
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

    /**
     * Validate URL attribute
     *
     * Checks 'href' attribute for XSS vectors.
     *
     * @param array $attrs Attributes from token
     *
     * @return bool True if URL is safe
     */
    public function validateAttributes(array $attrs): bool
    {
        // [url]text[/url] has no attributes, href comes from content
        if (!isset($attrs['href'])) {
            return true;
        }

        return $this->validator->validate($attrs['href']);
    }
}
