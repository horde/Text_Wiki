<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Cowiki\Tag;

use Horde\Text\Wiki\AbstractTagDefinition;
use Horde\Text\Wiki\TagType;

/**
 * Code block tag definition for Cowiki
 *
 * Cowiki code syntax: <code>...</code>
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class CodeTag extends AbstractTagDefinition
{
    public function __construct()
    {
        parent::__construct('code', TagType::BLOCK);
    }

    public function shouldParseContent(): bool
    {
        return false;
    }
}
