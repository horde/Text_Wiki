<?php

namespace Horde\Text\Wiki;

class TikiRendererColortext extends WikiRender
{
    public $colors = [
        'aqua',
        'black',
        'blue',
        'fuchsia',
        'gray',
        'green',
        'lime',
        'maroon',
        'navy',
        'olive',
        'purple',
        'red',
        'silver',
        'teal',
        'white',
        'yellow',
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
        if (isset($options['color']) && !in_array($options['color'], $this->colors)) {
            $options['color'] = '#' . $options['color'];
        }

        if ($options['type'] == 'start') {
            return '~~' . $options['color'] . ':';
        }

        if ($options['type'] == 'end') {
            return '~~';
        }
    }
}
