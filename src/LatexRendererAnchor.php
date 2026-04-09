<?php

namespace Horde\Text\Wiki;

/**
*
* This class renders an anchor target name in LaTeX.
*
* $Id$
*
* @author Jeremy Cowgar <jeremy@cowgar.com>
*
* @package Text_Wiki
*
*/

class LatexRendererAnchor extends WikiRendererBase
{
    public function token($options)
    {
        $type = '';
        extract($options); // $type, $name

        if ($type == 'start') {
            //return sprintf('<a id="%s">',$name);
            return '';
        }

        if ($type == 'end') {
            //return '</a>';
            return '';
        }
    }
}
