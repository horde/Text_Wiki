<?php

namespace Horde\Text\Wiki;

class PlainRendererNewline extends WikiRendererBase
{
    public function token($options)
    {
        return "\n";
    }
}
