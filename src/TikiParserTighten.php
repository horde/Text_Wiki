<?php

namespace Horde\Text\Wiki;

/**
*
* The rule removes all remaining newlines.
*
* @category Text
*
* @package Text_Wiki
*
* @author Justin Patrin <papercrane@reversefold.com>
* @author Paul M. Jones <pmjones@php.net>
*
* @license LGPL
*
* @version $Id$
*
*/


/**
*
* The rule removes all remaining newlines.
*
* @category Text
*
* @package Text_Wiki
*
* @author Justin Patrin <papercrane@reversefold.com>
* @author Paul M. Jones <pmjones@php.net>
*
*/

class TikiParserTighten extends WikiParserBase
{
    /**
    *
    * Apply tightening directly to the source text.
    *
    * @access public
    *
    */

    public function parse()
    {
        $this->wiki->source = str_replace(
            "\n",
            '',
            $this->wiki->source
        );
    }
}
