<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Tiki\Tag;

use Horde\Text\Wiki\AbstractTagDefinition;
use Horde\Text\Wiki\TagType;

/**
 * Image tag definition for Tiki
 *
 * Tiki image syntax: {img src="url" alt="text"}
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class ImageTag extends AbstractTagDefinition
{
    public function __construct()
    {
        parent::__construct('image', TagType::BLOCK);
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
