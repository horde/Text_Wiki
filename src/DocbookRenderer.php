<?php

namespace Horde\Text\Wiki;

// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Format class for the Docbook rendering
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
 * Format class for the Docbook rendering
 *
 * @category   Text
 * @package    Text_Wiki_Docbook
 * @author     bertrand Gugger <bertrand@toggg.com>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki_Docbook
 */
class DocbookRenderer extends WikiRendererBase
{
    public $conf = [
        'translate' => HTML_ENTITIES,
        'quotes'    => ENT_COMPAT,
        'charset'   => 'ISO-8859-1',
    ];

    public function pre()
    {
        return;
    }

    public function post()
    {
        return;
    }

}
