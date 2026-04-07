<?php

namespace Horde\Text\Wiki;

class PlainRendererUrl extends WikiRendererBase
{
    /**
     * Configuration options:
     * - 'show_url': bool - Whether to show URL in parentheses for described links
     *               Default: true (show URL for completeness in plain text)
     */
    public $conf = [
        'show_url' => true,
    ];

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
            // For described links, optionally show both text and URL
            if (!empty($options['text']) && $options['text'] != $options['href']) {
                if ($this->getConf('show_url', true)) {
                    return $options['text'] . ' (' . $options['href'] . ')';
                }
                // If show_url is false, only show the link text
                return $options['text'];
            }
            // For plain URLs, just show the URL
            return $options['text'];
        }
    }
}
