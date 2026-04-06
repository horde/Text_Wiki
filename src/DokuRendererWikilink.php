<?php

namespace Horde\Text\Wiki;

class DokuRendererWikilink extends WikiRendererBase
{
    /**
    *
    * Renders a token into XHTML.
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
        if (!isset($options['type'])) {
            return '[[' . $options['page']
                . (strlen($options['anchor']) ? '#' . $options['anchor'] : '')
                . (strlen($options['text']) && $options['page'] != $options['text'] ? '|' . $options['text'] : '') . ']]';
        } elseif ($options['type'] == 'start') {
            return '[[' . $options['page']
                . (strlen($options['anchor']) ? '#' . $options['anchor'] : '')
                . (strlen($options['text']) && $options['page'] != $options['text'] ? '|' . $options['text'] : '');
        } else {
            return ']]';
        }
    }
}
