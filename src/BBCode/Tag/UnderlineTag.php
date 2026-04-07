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
 * [u]underlined text[/u] tag definition
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class UnderlineTag extends AbstractTagDefinition
{
    public function __construct()
    {
        parent::__construct('u', TagType::INLINE);
    }
}
