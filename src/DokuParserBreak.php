<?php

namespace Horde\Text\Wiki;

/**
*
* Parses for explicit line breaks.
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
* Parses for explicit line breaks.
*
* This class implements a Text_Wiki_Parse to mark forced line breaks in the
* source text.
*
* @category Text
*
* @package Text_Wiki
*
* @author Paul M. Jones <pmjones@php.net>
*
*/

//None in DokuWiki
class DokuParserBreak extends WikiParserBase
{
    /**
    *
    * The regular expression used to parse the source text and find
    * matches conforming to this rule.  Used by the parse() method.
    *
    * @access public
    *
    * @var string
    *
    * @see parse()
    *
    */

    public $regex = '/ \\\n/';


    /**
    *
    * Generates a replacement token for the matched text.
    *
    * @access public
    *
    * @param array &$matches The array of matches from parse().
    *
    * @return string A delimited token to be used as a placeholder in
    * the source text.
    *
    */

    public function process($matches)
    {
        return $this->wiki->addToken($this->rule);
    }
}
