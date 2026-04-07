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
 * Break tag definition for Creole
 *
 * Creole break syntax: \\ (double backslash) or %%% (triple percent)
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class BreakTag extends AbstractTagDefinition
{
    public function __construct()
    {
        parent::__construct('break', TagType::INLINE);
    }

    public function requiresClosing(): bool
    {
        return false;
    }

    public function shouldParseContent(): bool
    {
        return false;
    }
}
