<?php

namespace Horde\Text\Wiki;

/**
*
* Parses for text marked as "raw" (i.e., to be rendered as-is).
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
* Parses for text marked as "raw" (i.e., to be rendered as-is).
*
* This class implements a Text_Wiki rule to find sections of the source
* text that are not to be processed by Text_Wiki.  These blocks of "raw"
* text will be rendered as they were found.
*
* @category Text
*
* @package Text_Wiki
*
* @author Paul M. Jones <pmjones@php.net>
*
*/

class DokuParserRaw extends WikiParserBase
{
    /**
    *
    * The regular expression used to find source text matching this
    * rule.
    *
    * @access public
    *
    * @var string
    *
    */

    public $regex = "/\n<nowiki>\n(.*?)\n<\/nowiki>\n/";

    /**
    *
    * Generates a token entry for the matched text.  Token options are:
    *
    * 'text' => The full matched text.
    *
    * @access public
    *
    * @param array &$matches The array of matches from parse().
    *
    * @return A delimited token number to be used as a placeholder in
    * the source text.
    *
    */

    public function parse()
    {
        $this->wiki->source = preg_replace_callback(
            $this->regex,
            [$this, 'process'],
            $this->wiki->source
        );

        $this->wiki->source = preg_replace_callback(
            '/%%(.*?)%%/',
            [$this, 'process'],
            $this->wiki->source
        );

    }

    public function process($matches)
    {
        $options = ['text' => $matches[1]];
        return "\n" . $this->wiki->addToken($this->rule, $options) . "\n";
    }
}
