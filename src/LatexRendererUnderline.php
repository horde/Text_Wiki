<?php

namespace HordeTextWiki;

class LatexRendererUnderline extends WikiRender
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
            return '\underline{';
        }

        if ($options['type'] == 'end') {
            return '}';
        }
    }
}
