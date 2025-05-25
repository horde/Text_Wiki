<?php

namespace Horde\Text\Wiki;

//no similar in Doku, using <html>
class DokuRendererColortext extends WikiRendererBase
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
        if (!in_array($options['color'], $this->colors)) {
            $options['color'] = '#' . $options['color'];
        }

        if ($options['type'] == 'start') {
            return '<html><span style="color: ' . $options['color'] . ';"></html>';
        }

        if ($options['type'] == 'end') {
            return '<html></span></html>';
        }
    }
}
