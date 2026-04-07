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
 * Wikilink tag definition for Creole
 *
 * Creole wikilink syntax: [[PageName]] or [[PageName|display text]]
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class WikilinkTag extends AbstractTagDefinition
{
    public function __construct()
    {
        parent::__construct('wikilink', TagType::INLINE);
    }

    public function validateAttributes(array $attributes): bool
    {
        return isset($attributes['page']) && $attributes['page'] !== '';
    }
}
