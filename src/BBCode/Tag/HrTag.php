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
use Horde\Text\Wiki\TagType;

/**
 * [hr] horizontal rule tag definition (self-closing)
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class HrTag extends AbstractTagDefinition
{
    public function __construct()
    {
        parent::__construct('hr', TagType::BLOCK);
    }

    /**
     * [hr] is self-closing, no [/hr] required
     *
     * @return bool False - no closing tag needed
     */
    public function requiresClosing(): bool
    {
        return false;
    }

    /**
     * [hr] has no content to parse
     *
     * @return bool False - no content
     */
    public function shouldParseContent(): bool
    {
        return false;
    }
}
