<?php

namespace Horde\Text\Wiki;

//none in Dokuwiki, using <html>
class DokuRendererCenter extends WikiRendererBase
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
        if ($options['type'] == 'start') {
            return '<html><div style="text-align: center;"></html>';
        }

        if ($options['type'] == 'end') {
            return '<html></div></html>';
        }
    }
}
