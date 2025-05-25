<?php

namespace Horde\Text\Wiki;

class PlainRendererHeading extends WikiRendererBase
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
