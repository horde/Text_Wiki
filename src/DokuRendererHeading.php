<?php

namespace Horde\Text\Wiki;

class DokuRendererHeading extends WikiRendererBase
{
    public function token($options)
    {
        return ($options['type'] == 'end' ? ' ' : "\n") .
            str_pad('', 7 - $options['level'], '=') .
            ($options['type'] == 'start' ? ' ' : '');
    }
}
