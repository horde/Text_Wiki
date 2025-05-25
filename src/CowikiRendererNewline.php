<?php

namespace Horde\Text\Wiki;

class CowikiRendererNewline extends WikiRender
{
    public function token($options)
    {
        return "\n";
        //return "\\\\\n";
    }
}
