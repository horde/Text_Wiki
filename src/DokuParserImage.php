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

class DokuParserImage extends WikiParse
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

    public $regex = '/({{)(\s*)(wiki:|https?:\/\/|ftp:\/\/)(.+?)(\s*)(}})/i';


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
        if ($matches[3] != 'wiki:') {
            $matches[4] = $matches[3] . $matches[4];
        }

        $pos = strpos($matches[4], '?');
        if ($pos === false) {
            $options = [
                'src' => $matches[4],
                'attr' => []];
        } else {
            $options = ['src' => substr($matches[4], 0, $pos)];
            $attr = substr($matches[4], $pos + 1);
            $parts = explode('x', $attr);
            if (isset($parts[0]) && $parts[0] != '') {
                $options['attr']['width'] = $parts[0];
            }
            if (isset($parts[1]) && $parts[1] != '') {
                $options['attr']['height'] = $parts[1];
            }
        }

        if (strlen($matches[2]) && strlen($matches[5])) {
            $options['attr']['align'] = 'center';
        } elseif (strlen($matches[2])) {
            $options['attr']['align'] = 'right';
        } elseif (strlen($matches[5])) {
            $options['attr']['align'] = 'left';
        }

        return $this->wiki->addToken($this->rule, $options);
    }
}
