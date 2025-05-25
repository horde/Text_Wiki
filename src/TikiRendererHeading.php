<?php

namespace Horde\Text\Wiki;

class TikiRendererHeading extends WikiRendererBase
{
    public function token($options)
    {
        if ($options['type'] == 'end') {
            return "\n";
        } elseif ($options['type'] == 'start') {
            return str_pad('', $options['level'], '!');
        }
    }
}
