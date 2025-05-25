<?php
namespace Horde\Text\Wiki;

class Text_Wiki_Render_CoWiki_Newline extends WikiRender {
    
    
    function token($options)
    {
        return "\n";
        //return "\\\\\n";
    }
}

?>