<?php

namespace HordeTextWiki;

class DokuRendererHeading extends WikiRender
{
    public function token($options)
    {
        return ($options['type'] == 'end' ? ' ' : "\n") .
            str_pad('', 7 - $options['level'], '=') .
            ($options['type'] == 'start' ? ' ' : '');
    }
}
