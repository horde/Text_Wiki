<?php
namespace Horde\Text\Wiki;

class Text_Wiki_Render_Creole_Heading extends WikiRender {
    function token($options)
    {
        if ($options['type'] == 'start') {
            return str_pad('', $options['level'], '=') . ' ';
        }
        else if ($options['type'] == 'end') {
            // next line would add trailing '=' signs
            // return ' ' . str_pad('', $options['level'], '=') . "\n\n";
            return "\n\n";
        }
    }
}
?>