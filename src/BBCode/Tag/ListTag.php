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
use Horde\Text\Wiki\TagDefinition;
use Horde\Text\Wiki\TagType;

/**
 * [list]...[/list] tag definition
 *
 * Can only contain [*] list items and nested [list] tags.
 *
 * @author   Bertrand Gugger <bertrand@toggg.com>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class ListTag extends AbstractTagDefinition
{
    public function __construct()
    {
        parent::__construct('list', TagType::BLOCK);
    }

    /**
     * List can only contain [*] and nested [list]
     *
     * @param TagDefinition $child Child tag to check
     *
     * @return bool True if child is [*] or [list]
     */
    public function canContain(TagDefinition $child): bool
    {
        return $child->getName() === 'listitem' || $child->getName() === 'list';
    }
}
