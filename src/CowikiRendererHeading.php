<?php

namespace HordeTextWiki;

class CowikiRendererHeading extends WikiRender
{
    public function token($options)
    {
        if ($options['type'] == 'start') {
            return str_pad('', $options['level'], '+') . ' ';
        } else {
            return '';
        }
    }
}
