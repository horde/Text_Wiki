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
 * URL attribute validator for XSS prevention
 *
 * Validates URL attributes to prevent XSS attacks via javascript:,
 * data:, and other dangerous schemes.
 *
 * @author   Bertrand Gugger <bertrand@toggg.com>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class UrlAttributeValidator
{
    /**
     * Allowed URL schemes (whitelist)
     *
     * @var array<string>
     */
    private const ALLOWED_SCHEMES = [
        'http',
        'https',
        'ftp',
        'ftps',
        'mailto',
        'news',
        'nntp',
        'tel',
        'ssh',
        'sftp',
    ];

    /**
     * Validate a URL attribute value
     *
     * Checks:
     * - URL scheme is in whitelist (no javascript:, data:, etc.)
     * - URL is properly formatted
     * - Relative URLs are allowed (no scheme)
     *
     * @param string $url URL to validate
     *
     * @return bool True if URL is safe, false otherwise
     */
    public function validate(string $url): bool
    {
        $url = trim($url);

        // Empty URLs are invalid
        if ($url === '') {
            return false;
        }

        // Relative URLs are allowed (no scheme)
        if (!str_contains($url, ':')) {
            return true;
        }

        // Extract scheme
        $parsed = parse_url($url);
        if ($parsed === false) {
            return false;
        }

        // No scheme means relative URL (allowed)
        if (!isset($parsed['scheme'])) {
            return true;
        }

        // Check scheme against whitelist
        $scheme = strtolower($parsed['scheme']);
        return in_array($scheme, self::ALLOWED_SCHEMES, true);
    }
}
