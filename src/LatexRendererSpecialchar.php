<?php

namespace HordeTextWiki;

// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Specialchar rule end renderer for Latex
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * This class renders special characters in Latex.
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 */
class LatexRendererSpecialchar extends WikiRender
{
    public $types = ['~bs~' => '\\\\',
        '~hs~' => '\hspace{1em}',
        '~amp~' => '\&',
        '~ldq~' => '``',
        '~rdq~' => "''",
        '~lsq~' => '`',
        '~rsq~' => "'",
        '~c~' => '\copyright',
        '~--~' => '---',
        '" -- "' => '---',
        '&quot; -- &quot;' => '---',
        '~lt~' => '<',
        '~gt~' => '>'];

    public function token($options)
    {
        if (isset($this->types[$options['char']])) {
            return $this->types[$options['char']];
        } else {
            return $options['char'];
        }
    }
}
