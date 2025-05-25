<?php

namespace HordeTextWiki;

class CreoleRendererInclude extends WikiRender
{
    public function token()
    {
        if ($options['type'] == 'start') {
            return "{{";
        }

        if ($options['type'] == 'end') {
            return "}}";
        }
    }
}
