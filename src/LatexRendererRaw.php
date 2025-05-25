<?php

namespace HordeTextWiki;

class LatexRendererRaw extends WikiRender
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
        return "Raw: " . $options['text'];
    }
}
