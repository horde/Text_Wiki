<?php

namespace Horde\Text\Wiki;

/**
*
* Parses for image placement.
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
* Parses for image placement.
*
* @category Text
*
* @package Text_Wiki
*
* @author Paul M. Jones <pmjones@php.net>
*
*/

//Not used in CoWiki
class CowikiParserImage extends WikiParserBase
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

    public $regex = '/({img )(.+?)(})/i';


    /**
    *
    * Generates a token entry for the matched text.  Token options are:
    *
    * 'src' => The image source, typically a relative path name.
    *
    * 'opts' => Any macro options following the source.
    *
    * @access public
    *
    * @param array &$matches The array of matches from parse().
    *
    * @return A delimited token number to be used as a placeholder in
    * the source text.
    *
    */

    public function process(&$matches)
    {
        $pos = strpos($matches[2], ' ');

        if ($pos === false) {
            $options = [
                'src' => $matches[2],
                'attr' => []];
        } else {
            // everything after the space is attribute arguments
            $options = [
                'src' => substr($matches[2], 0, $pos),
                'attr' => $this->getAttrs(substr($matches[2], $pos + 1)),
            ];
        }

        return $this->wiki->addToken($this->rule, $options);
    }
}
