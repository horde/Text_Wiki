<?php

namespace Horde\Text\Wiki;

// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Tt rule end renderer for Docbook
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki_Docbook
 * @author     bertrand Gugger <bertrand@toggg.com>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/Text_Wiki_Docbook
 */

/**
 * This class renders monospaced text in DocBook.
 *
 * @category   Text
 * @package    Text_Wiki_Docbook
 * @author     bertrand Gugger <bertrand@toggg.com>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki_Docbook
 */
class DocbookRendererTt extends WikiRendererBase
{
    /**
    *
    * Renders a token into text matching the requested format.
    *
    * @access public
    *
    * @param array $options The "options" portion of the token (second
    * element).
    *
    * @return string The text rendered from the token options.
    *
    */

    public function token($options)
    {
        if ($options['type'] == 'start') {
            return "<code>";
        }
        return '</code>';
    }
}
