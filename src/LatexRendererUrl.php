<?php

namespace HordeTextWiki;

class LatexRendererUrl extends WikiRender
{
    public $conf = [
        'target' => false,
        'images' => true,
        'img_ext' => ['jpg', 'jpeg', 'gif', 'png'],
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
        // create local variables from the options array (text,
        // href, type)
        extract($options);

        if ($options['type'] == 'start') {
            return '';
        } elseif ($options['type'] == 'end') {
            return '\footnote{' . $href . '}';
        } else {
            return $text . '\footnote{' . $href . '}';
        }
    }
}
