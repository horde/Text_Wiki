<?php

namespace HordeTextWiki;

class PlainRendererNewline extends WikiRender
{
    public function token($options)
    {
        return "\n";
    }
}
