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

class PlainRendererAnchor extends WikiRendererBase
{
    public function token($options)
    {
        return $options['name'];
    }
}
