<?php

namespace HordeTextWiki;

class PlainRendererWikilink extends WikiRender
{
    /**
    *
    * Renders a token into plain text.
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
        return $options['text'];
    }
}
