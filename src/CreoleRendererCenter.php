<?php

namespace Horde\Text\Wiki;

//There is no center rule for Creole, so use a simple table
class CreoleRendererCenter extends WikiRendererBase
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
            return "| ";
        }

        if ($options['type'] == 'end') {
            return "\n\n";
        }

        return '';
    }
}
