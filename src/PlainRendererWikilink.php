<?php

namespace Horde\Text\Wiki;

class PlainRendererWikilink extends WikiRendererBase
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
        // If no custom text was provided, use the page name
        if (empty($options['text'])) {
            return $options['page'];
        }
        return $options['text'];
    }
}
