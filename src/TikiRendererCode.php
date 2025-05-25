<?php

namespace Horde\Text\Wiki;

class TikiRendererCode extends WikiRendererBase
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
        $ret = '{CODE(';
        if (isset($options['attr']) && $options['attr']['type']) {
            $ret .= 'colors=>' . $options['attr']['type'];
        }
        return $ret . ")}\n" . $options['text'] . "\n{CODE}";
    }
}
