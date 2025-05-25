<?php

namespace HordeTextWiki;

class PlainRendererHeading extends WikiRender
{
    public function token($options)
    {
        if ($options['type'] == 'end') {
            return "\n\n";
        } else {
            return "\n";
        }
    }
}
