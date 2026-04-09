<?php

namespace Horde\Text\Wiki;

class LatexRendererTt extends WikiRendererBase
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
            return '\texttt{';
        }

        if ($options['type'] == 'end') {
            return '}';
        }

        return '';
    }
}
