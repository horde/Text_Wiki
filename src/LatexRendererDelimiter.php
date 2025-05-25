<?php

namespace HordeTextWiki;

class LatexRendererDelimiter extends WikiRender
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
        // TODO: Is this where I can do some LaTeX escaping for items
        // such as $ { } _ ?
        return "Delimiter: " . $options['text'];
    }
}
