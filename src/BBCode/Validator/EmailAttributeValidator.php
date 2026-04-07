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
 * Email attribute validator
 *
 * Validates email addresses for [email] tags.
 *
 * @author   Bertrand Gugger <bertrand@toggg.com>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class EmailAttributeValidator
{
    /**
     * Validate an email attribute value
     *
     * Uses PHP's filter_var with FILTER_VALIDATE_EMAIL.
     * For [email=address@example.com] form, validates 'email' attribute.
     * For [email]address@example.com[/email] form, no validation needed here
     * (content will be validated during rendering).
     *
     * @param array $attrs Attributes array from token
     *
     * @return bool True if valid or no email attribute present
     */
    public function validate(array $attrs): bool
    {
        // If no 'email' attribute, it's valid (content-based form)
        if (!isset($attrs['email'])) {
            return true;
        }

        $email = trim($attrs['email']);

        // Empty email is invalid
        if ($email === '') {
            return false;
        }

        // Use PHP's built-in email validation
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
