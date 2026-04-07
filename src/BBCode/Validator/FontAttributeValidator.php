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
 * Font attribute validator for CSS injection prevention
 *
 * Validates font family names to prevent CSS injection attacks.
 * Allows letters, numbers, spaces, hyphens, and common punctuation.
 *
 * @author   Bertrand Gugger <bertrand@toggg.com>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class FontAttributeValidator
{
    /**
     * Validate a font family attribute value
     *
     * Accepts:
     * - Font names with letters, numbers, spaces, hyphens
     * - Multiple fonts separated by commas
     * - Generic families: serif, sans-serif, monospace, etc.
     *
     * Rejects:
     * - Semicolons (CSS injection)
     * - Quotes within font names (already handled by tokenizer)
     * - Special characters that could break CSS
     *
     * @param string $font Font family value to validate
     *
     * @return bool True if font is safe, false otherwise
     */
    public function validate(string $font): bool
    {
        $font = trim($font);

        // Empty fonts are invalid
        if ($font === '') {
            return false;
        }

        // Reject injection attempts (semicolons, etc.)
        if (str_contains($font, ';')) {
            return false;
        }

        // Reject newlines and control characters
        if (preg_match('/[\x00-\x1F\x7F]/', $font)) {
            return false;
        }

        // Allow: letters, numbers, spaces, hyphens, commas, apostrophes
        // Font names like "Times New Roman" or 'Courier New' or Arial, Helvetica
        if (!preg_match('/^[a-zA-Z0-9\s\-,\']+$/', $font)) {
            return false;
        }

        return true;
    }
}
