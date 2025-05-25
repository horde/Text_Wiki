<?php

namespace HordeTextWiki;

class LatexRendererNewline extends WikiRender
{
    public function token($options)
    {
        return "\\newline\n";
    }
}
