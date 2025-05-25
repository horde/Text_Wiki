<?php

namespace HordeTextWiki;

class CreoleRendererBlockquote extends WikiRender
{
    public $css_stack = [];

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
            if (empty($options['css'])) {
                $options['css'] = '';
            }
            array_push($this->css_stack, $options['css']);
            $this->wiki->registerRenderCallback([&$this, 'renderInsideText']);
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
        $text = trim($text);
        if (array_pop($this->css_stack) == 'remark') {
            $text = preg_replace('/(^|\n)([\>\:]*) */', '\1:\2 ', $text);
        } else {
            $text = preg_replace('/(^|\n)([\>\:]*) */', '\1>\2 ', $text);
        }
        return $text . "\n\n";
    }
}
