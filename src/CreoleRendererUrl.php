<?php

namespace Horde\Text\Wiki;

class CreoleRendererUrl extends WikiRendererBase
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
        $type = '';
        $href = '';
        $text = '';
        extract($options);
        if ($type == 'start') {
            return '[[' . $href . '|';
        } elseif ($type == 'end') {
            return ']]';
        } else {
            $noprot = str_replace('http://', '', str_replace('mailto:', '', $href));
            if (strpos($href, "#ref") === 0 || strpos($href, "#fn") === 0) {
                return $text;
            } elseif (! strlen($text) || $text == $href || $text == $noprot) {
                return '[[' . $href . ']]';
            } else {
                return '[[' . $href . '|' . $text . ']]';
            }
        }
    }
}
