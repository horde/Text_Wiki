<?php

namespace Horde\Text\Wiki;

/**
*
* Render for wiki redirects.
*
* @category Text
*
* @package Text_Wiki
*
* @author Rodrigo Sampaio Primo <rodrigo@utopia.org.br>
*
* @license LGPL
*
*/

/**
*
* Render for wiki redirects.
*
* This class implements a Text_Wiki_Render to output text marked to
* be a wiki redirect.
*
* @category Text
*
* @package Text_Wiki
*
* @author Rodrigo Sampaio Primo <rodrigo@utopia.org.br>
*
*/

class TikiRendererRedirect extends WikiRendererBase
{
    public function token($options)
    {
        if ($options['type'] == 'end') {
            return '"}';
        } elseif ($options['type'] == 'start') {
            return '{redirect page="';
        }
    }
}
