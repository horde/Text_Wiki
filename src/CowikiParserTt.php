<?php

namespace Horde\Text\Wiki;

/**
*
* Find source text marked for teletype (monospace).
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
* Find source text marked for teletype (monospace).
*
* Defined by text surrounded by two curly braces. On parsing, the text
* itself is left in place, but the starting and ending instances of
* curly braces are replaced with tokens.
*
* Token options are:
*
* 'type' => ['start'|'end'] The starting or ending point of the
* teletype text.  The text itself is left in the source.
*
* @category Text
*
* @package Text_Wiki
*
* @author Paul M. Jones <pmjones@php.net>
*
*/

class CowikiParserTt extends WikiParserBase
{
    /**
    *
    * The regular expression used to parse the source text.
    *
    * @access public
    *
    * @var string
    *
    * @see parse()
    *
    */

    public $regex = "/=({*?.*}*?)=/U";


    /**
    *
    * Generates a replacement for the matched text.
    *
    * @access public
    *
    * @param array $matches The array of matches from parse().
    *
    * @return string A pair of delimited tokens to be used as a
    * placeholder in the source text surrounding the teletype text.
    *
    */

    public function process($matches)
    {
        $start = $this->wiki->addToken(
            $this->rule,
            ['type' => 'start']
        );

        $end = $this->wiki->addToken(
            $this->rule,
            ['type' => 'end']
        );

        return $start . $matches[1] . $end;
    }
}
