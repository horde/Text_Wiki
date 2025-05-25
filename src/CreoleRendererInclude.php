<?php
namespace HordeTextWiki;

class Text_Wiki_Render_Creole_Include extends WikiRender {

    function token()
    {
        if ($options['type'] == 'start') {
            return "{{";
        }

        if ($options['type'] == 'end') {
            return "}}";
        }
    }
}
?>