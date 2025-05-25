<?php

namespace Horde\Text\Wiki;

class TikiRendererNewline extends WikiRendererBase
{
    public function token($options)
    {
        return "\n";
    }
}
