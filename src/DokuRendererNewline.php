<?php
namespace HordeTextWiki;

class Text_Wiki_Render_Doku_Newline extends WikiRender {
    
    
    function token($options)
    {
        return "\n";
        //return "\\\\\n";
    }
}

?>