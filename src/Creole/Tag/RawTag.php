<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Creole\Tag;

use Horde\Text\Wiki\AbstractTagDefinition;
use Horde\Text\Wiki\TagType;

/**
 * Raw/escape tag definition for Creole
 *
 * Creole escape syntax: ~char (tilde escapes next special character)
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class RawTag extends AbstractTagDefinition
{
    public function __construct()
    {
        parent::__construct('raw', TagType::BLOCK);
    }

    public function shouldParseContent(): bool
    {
        return false;
    }
}
