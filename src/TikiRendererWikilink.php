<?php

namespace Horde\Text\Wiki;

class TikiRendererWikilink extends WikiRendererBase
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
        if (isset($options['type'])) {
            if ($options['type'] == 'start') {
                return '((' . $options['page'] .
                    (isset($options['anchor']) && strlen($options['anchor']) ? '#' . $options['anchor'] : '') .
                    (strlen($options['text']) /*&& $options['page'] != $options['text']*/ ? '|' : '');
            } else {
                return '))';
            }
        } else {
            return '((' . $options['page'] .
                (isset($options['anchor']) && strlen($options['anchor']) ? '#' . $options['anchor'] : '') .
                (isset($options['text']) && strlen($options['text']) && $options['page'] != $options['text'] ? '|' . $options['text'] : '') .
                '))';
        }
    }
}
