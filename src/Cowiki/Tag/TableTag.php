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
use Horde\Text\Wiki\TagDefinition;
use Horde\Text\Wiki\TagType;

/**
 * Table tag definition for Cowiki
 *
 * Cowiki table syntax: <table><tr><td>...</td></tr></table>
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class TableTag extends AbstractTagDefinition
{
    public function __construct()
    {
        parent::__construct('table', TagType::BLOCK);
    }

    public function canContain(TagDefinition $child): bool
    {
        return $child->getName() === 'row';
    }
}
