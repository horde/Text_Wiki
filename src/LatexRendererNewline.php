<?php
namespace HordeTextWiki;

class Text_Wiki_Render_Latex_Newline extends WikiRender {
    
    
    function token($options)
    {
        return "\\newline\n";
    }
}

?>