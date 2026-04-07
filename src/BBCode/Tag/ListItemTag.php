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
 * [*] list item tag definition
 *
 * Implicitly-closing tag: accepts children, gets auto-closed by next [*] or [/list].
 * No explicit [/*] closing tag needed.
 * Only valid inside [list].
 *
 * @author   Bertrand Gugger <bertrand@toggg.com>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class ListItemTag extends AbstractTagDefinition
{
    public function __construct()
    {
        parent::__construct('listitem', TagType::MIXED);
    }

    /**
     * [*] requires closing to accept children
     *
     * Gets implicitly closed by next [*] or parent [/list].
     * Not a true self-closing tag like <br/> or <hr/>.
     *
     * @return bool True (can have children, needs to be on stack)
     */
    public function requiresClosing(): bool
    {
        return true;
    }
}
