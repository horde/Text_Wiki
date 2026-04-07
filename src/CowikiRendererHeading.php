<?php

namespace Horde\Text\Wiki;

class CowikiRendererHeading extends WikiRendererBase
{
    public function token($options)
    {
        if ($options['type'] == 'start') {
            return str_pad('', $options['level'], '+') . ' ';
        } else {
            return "\n";
        }
    }
}
