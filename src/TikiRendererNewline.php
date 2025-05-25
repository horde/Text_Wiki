<?php

namespace HordeTextWiki;

class TikiRendererNewline extends WikiRender
{
    public function token($options)
    {
        return "\n";
    }
}
