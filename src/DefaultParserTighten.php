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
* @author Paul M. Jones <pmjones@php.net>
*
*/

class DefaultParserTighten extends WikiParserBase
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
