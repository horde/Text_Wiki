<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Doku\Tag;

use Horde\Text\Wiki\AbstractTagDefinition;
use Horde\Text\Wiki\TagType;

class WikilinkTag extends AbstractTagDefinition
{
    public function __construct()
    {
        parent::__construct('wikilink', TagType::INLINE);
    }

    public function validateAttributes(array $attrs): bool
    {
        return isset($attrs['page']) && $attrs['page'] !== '';
    }
}
