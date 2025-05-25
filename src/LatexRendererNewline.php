<?php

namespace Horde\Text\Wiki;

class LatexRendererNewline extends WikiRendererBase
{
    public function token($options)
    {
        return "\\newline\n";
    }
}
