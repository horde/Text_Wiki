<?php
namespace HordeTextWiki;

class Text_Wiki_Render_Plain_Heading extends WikiRender {
    
    function token($options)
    {
        if ($options['type'] == 'end') {
            return "\n\n";
        } else {
            return "\n";
        }
    }
}
?>