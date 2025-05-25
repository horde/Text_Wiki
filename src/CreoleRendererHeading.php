<?php

namespace Horde\Text\Wiki;

class CreoleRendererHeading extends WikiRender
{
    public function token($options)
    {
        if ($options['type'] == 'start') {
            return str_pad('', $options['level'], '=') . ' ';
        } elseif ($options['type'] == 'end') {
            // next line would add trailing '=' signs
            // return ' ' . str_pad('', $options['level'], '=') . "\n\n";
            return "\n\n";
        }
    }
}
