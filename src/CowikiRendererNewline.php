<?php

namespace Horde\Text\Wiki;

class CowikiRendererNewline extends WikiRendererBase
{
    public function token($options)
    {
        return "\n";
        //return "\\\\\n";
    }
}
