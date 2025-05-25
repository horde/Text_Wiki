<?php

namespace Horde\Text\Wiki;

class LatexRendererBlockquote extends WikiRendererBase
{
    public $conf = ['css' => null];

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
        $type = $options['type'];
        $level = $options['level'];

        // starting
        if ($type == 'start') {
            return "\\begin{quote}\n";
        }

        // ending
        if ($type == 'end') {
            return "\\end{quote}\n\n";
        }
    }
}
