<?php

namespace Horde\Text\Wiki;

// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Page rule end parser for tikiwiki
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Justin Patrin <papercrane@reversefold.com>
 * @author     Paul M. Jones <pmjones@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * This class parses page separators for tikiwiki (...page...) and replace them with a token
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Justin Patrin <papercrane@reversefold.com>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 */
class TikiParserPage extends WikiParserBase
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

    public $regex = '/\.\.\.page\.\.\./i';


    public function parse()
    {
        $this->wiki->source = preg_replace_callback($this->regex, [$this, 'process'], $this->wiki->source);

    }

    /**
    *
    * Generates a token entry for the matched text.  Token options are:
    *
    * <none>
    *
    * @access public
    *
    * @param array &$matches The array of matches from parse().
    *
    * @return A delimited token number to be used as a placeholder in
    * the source text.
    *
    */

    public function process($matches)
    {
        return $this->wiki->addToken($this->rule);
    }
}
