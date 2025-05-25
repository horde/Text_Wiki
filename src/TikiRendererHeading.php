<?php
namespace Horde\Text\Wiki;

class Text_Wiki_Render_Tiki_Heading extends WikiRender {
    function token($options)
    {
        if ($options['type'] == 'end') {
            return "\n";
        } else if ($options['type'] == 'start') {
            return str_pad('', $options['level'], '!');
        }
    }
}
