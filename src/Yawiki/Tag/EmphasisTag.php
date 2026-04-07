<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Yawiki\Tag;

use Horde\Text\Wiki\AbstractTagDefinition;
use Horde\Text\Wiki\TagType;

/**
 * Emphasis tag definition
 *
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   Justin Patrin <papercrane@reversefold.com>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class EmphasisTag extends AbstractTagDefinition
{
    public function __construct()
    {
        parent::__construct('emphasis', TagType::INLINE);
    }
}
