<?php

namespace HordeTextWiki;

/**
*
* Parses for text marked as a code example block.
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
* Parses for text marked as a code example block.
*
* This class implements a Text_Wiki_Parse to find sections marked as code
* examples.  Blocks are marked as the string <code> on a line by itself,
* followed by the inline code example, and terminated with the string
* </code> on a line by itself.  The code example is run through the
* native PHP highlight_string() function to colorize it, then surrounded
* with <pre>...</pre> tags when rendered as XHTML.
*
* @category Text
*
* @package Text_Wiki
*
* @author Paul M. Jones <pmjones@php.net>
*
*/

class DokuParserCode extends WikiParse
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

    public $regex = '/^(<code( (.+))?>)\n(.+)\n(<\/code>)$/Umsi';


    /**
    *
    * Generates a token entry for the matched text.  Token options are:
    *
    * 'text' => The full matched text, not including the <code></code> tags.
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
        // are there additional attribute arguments?
        $args = trim($matches[3]);

        if ($args == '') {
            $options = [
                'text' => $matches[4],
                'attr' => ['type' => ''],
            ];
        } else {
            // get the attributes...
            //$attr = $this->getAttrs($args);
            $attr['type'] = $args;

            // retain the options
            $options = [
                'text' => $matches[4],
                'attr' => $attr,
            ];
        }

        return $this->wiki->addToken($this->rule, $options);
    }
}
