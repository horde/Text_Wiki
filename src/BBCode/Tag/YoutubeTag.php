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
use Horde\Text\Wiki\BBCode\Validator\YoutubeAttributeValidator;
use Horde\Text\Wiki\TagType;

/**
 * [youtube]VIDEO_ID[/youtube] tag definition
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class YoutubeTag extends AbstractTagDefinition
{
    private YoutubeAttributeValidator $validator;

    public function __construct()
    {
        parent::__construct('youtube', TagType::BLOCK);
        $this->validator = new YoutubeAttributeValidator();
    }

    /**
     * [youtube] content is video ID, not parsed
     *
     * @return bool False - video ID is literal
     */
    public function shouldParseContent(): bool
    {
        return false;
    }

    /**
     * Validate YouTube video ID
     *
     * @param array $attrs Attributes from token
     *
     * @return bool True if video ID is valid
     */
    public function validateAttributes(array $attrs): bool
    {
        return $this->validator->validate($attrs);
    }
}
