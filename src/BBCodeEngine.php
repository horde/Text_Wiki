<?php

// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * BBCode: extension of base Text_Wiki text conversion handler
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
 * "master" class for handling the management and convenience
 */

namespace Horde\Text\Wiki;

/**
 * Base Text_Wiki handler class extension for BBCode
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 * @see        Text_Wiki::Text_Wiki()
 */
class BBCodeEngine extends TextWikiBase
{
    /**
     * The default list of rules, in order, to apply to the source text.
     *
     * @access public
     * @var array
     */
    public $rules = [
        'Prefilter',
        'Delimiter',
        'Code',
        //        'Plugin',
        //        'Function',
        //        'Html',
        //        'Raw',
        //        'Preformatted',
        //        'Include',
        //        'Embed',
        //        'Page',
        //        'Anchor',
        //        'Heading',
        //        'Toc',
        //        'Titlebar',
        //        'Horiz',
        //        'Break',
        'Blockquote',
        'List',
        //        'Deflist',
        //        'Table',
        //        'Box',
        'Image',
        'Smiley',
        //        'Phplookup',
        //        'Center',
        'Newline',
        'Paragraph',
        'Url',
        //        'Freelink',
        'Colortext',
        'Font',
        //        'Strong',
        'Bold',
        //        'Emphasis',
        'Italic',
        'Underline',
        //        'Tt',
        'Superscript',
        'Subscript',
        //        'Specialchar',
        //        'Revise',
        //        'Interwiki',
        'Tighten',
    ];

    /**
     * Constructor: just adds the path to BBCode rules
     *
     * @access public
     * @param array $rules The set of rules to load for this object.
     */
    public function __construct($rules = null)
    {
        parent::__construct($rules);
        $this->addPath('parse', $this->fixPath(dirname(__FILE__)) . 'Parse/BBCode');
    }
}
