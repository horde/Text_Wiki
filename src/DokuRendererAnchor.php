<?php

namespace Horde\Text\Wiki;

/**
*
* This class renders an anchor target name in XHTML.
*
* @author Manuel Holtgrewe <purestorm at ggnore dot net>
*
* @author Paul M. Jones <pmjones at ciaweb dot net>
*
* @package Text_Wiki
*
*/

class DokuRendererAnchor extends WikiRendererBase
{
    public function token($options)
    {
        extract($options); // $type, $name

        if ($options['type'] == 'start') {
            $css = $this->formatConf(' class="%s"', 'css');
            $format = "<html><a$css id=\"%s\"></html>";
            return sprintf($format, $options['name']);
        }

        if ($options['type'] == 'end') {
            return '<html></a></html>';
        }
    }
}
