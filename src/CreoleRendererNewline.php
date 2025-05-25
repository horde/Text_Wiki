<?php

namespace Horde\Text\Wiki;

class CreoleRendererNewline extends WikiRendererBase
{
    public function token($options)
    {
        return "\n";
    }
}
