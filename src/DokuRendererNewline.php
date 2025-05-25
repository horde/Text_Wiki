<?php

namespace Horde\Text\Wiki;

class DokuRendererNewline extends WikiRendererBase
{
    public function token($options)
    {
        return "\n";
        //return "\\\\\n";
    }
}
