<?php

namespace Horde\Text\Wiki;

class CreoleRendererInclude extends WikiRendererBase
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
