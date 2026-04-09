<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\BBCode;

use Horde\Text\Wiki\AbstractTagDefinition as BaseAbstractTagDefinition;

/**
 * BBCode-specific tag definition base class
 *
 * Extends the format-agnostic AbstractTagDefinition.
 * Kept for backward compatibility with existing BBCode tags.
 *
 * @author   Bertrand Gugger <bertrand@toggg.com>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
abstract class AbstractTagDefinition extends BaseAbstractTagDefinition {}
