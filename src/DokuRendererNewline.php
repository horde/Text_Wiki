<?php

namespace HordeTextWiki;

class DokuRendererNewline extends WikiRender
{
    public function token($options)
    {
        return "\n";
        //return "\\\\\n";
    }
}
