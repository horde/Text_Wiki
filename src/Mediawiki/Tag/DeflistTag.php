<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Mediawiki\Tag;

use Horde\Text\Wiki\AbstractTagDefinition;
use Horde\Text\Wiki\TagDefinition;
use Horde\Text\Wiki\TagType;

class DeflistTag extends AbstractTagDefinition
{
    public function __construct()
    {
        parent::__construct('deflist', TagType::BLOCK);
    }

    public function canContain(TagDefinition $child): bool
    {
        $name = $child->getName();

        return $name === 'defterm' || $name === 'defdef';
    }
}
