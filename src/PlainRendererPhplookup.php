<?php

namespace Horde\Text\Wiki;

class PlainRendererPhplookup extends WikiRendererBase
{
    public $conf = ['target' => '_blank'];

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
        return trim($options['text']);
    }
}
