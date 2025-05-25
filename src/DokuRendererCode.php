<?php

namespace Horde\Text\Wiki;

class DokuRendererCode extends WikiRendererBase
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
        return '<code' . (strlen($options['attr']['type']) ? ' ' . $options['attr']['type'] : '') . ">\n" . $options['text'] . "\n</code>";
    }
}
