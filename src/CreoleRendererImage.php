<?php

namespace Horde\Text\Wiki;

class CreoleRendererImage extends WikiRendererBase
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
        if (!strlen($options['attr']['alt']) || $options['src'] == $options['attr']['alt']) {
            return '{{' . $options['src'] . '}}';
        } else {
            return '{{' . $options['src'] . '|' . $options['attr']['alt'] . '}}';
        }
    }
}
