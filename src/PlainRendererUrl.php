<?php

namespace Horde\Text\Wiki;

class PlainRendererUrl extends WikiRendererBase
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
        if ($options['type'] == 'start' || $options['type'] == 'end') {
            return '';
        } else {
            // For described links, show both text and URL
            if (!empty($options['text']) && $options['text'] != $options['href']) {
                return $options['text'] . ' (' . $options['href'] . ')';
            }
            // For plain URLs, just show the URL
            return $options['text'];
        }
    }
}
