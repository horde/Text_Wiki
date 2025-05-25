<?php

namespace HordeTextWiki;

class CreoleRendererNewline extends WikiRender
{
    public function token($options)
    {
        return "\n";
    }
}
