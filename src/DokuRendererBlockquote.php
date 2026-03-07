<?php

namespace Horde\Text\Wiki;

class DokuRendererBlockquote extends WikiRendererBase
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
        // starting
        if ($options['type'] == 'start') {
            $this->wiki->registerRenderCallback([$this, 'renderInsideText']);
            return '';
        }
        // ending
        if ($options['type'] == 'end') {
            $this->wiki->popRenderCallback();
            return '';
        }
    }

    public function renderInsideText($text)
    {
        $text = preg_replace('/(^|\n)(?!$)/', '\1>', $text);
        return $text;
    }
}
