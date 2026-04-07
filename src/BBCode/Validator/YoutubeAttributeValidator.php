<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\BBCode\Validator;

/**
 * YouTube video ID validator
 *
 * Validates YouTube video IDs for [youtube] tags to prevent XSS.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class YoutubeAttributeValidator
{
    /**
     * Validate a YouTube video ID
     *
     * YouTube video IDs are 11 characters: alphanumeric, underscore, hyphen.
     * Example: dQw4w9WgXcQ
     *
     * For [youtube]VIDEO_ID[/youtube] form, no validation needed here
     * (content will be validated during rendering).
     *
     * @param array $attrs Attributes array from token
     *
     * @return bool Always true (validation happens during rendering)
     */
    public function validate(array $attrs): bool
    {
        // YouTube tag uses content, not attributes
        // Validation happens in renderer
        return true;
    }

    /**
     * Validate YouTube video ID string
     *
     * @param string $videoId Video ID to validate
     *
     * @return bool True if valid YouTube video ID format
     */
    public function validateVideoId(string $videoId): bool
    {
        $videoId = trim($videoId);

        // YouTube video IDs are exactly 11 characters
        // Allowed: alphanumeric, underscore, hyphen
        return preg_match('/^[a-zA-Z0-9_-]{11}$/', $videoId) === 1;
    }
}
