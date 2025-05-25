<?php

namespace HordeTextWiki;

class TikiRendererImage extends WikiRender
{
    /**
     * Defines rule specific configuration
     *
     * @var array
     */
    public $conf = [
        'prefix' => 'img/wiki_up/', // path to the wiki image directory
    ];

    /**
    *
    * Renders a token into text matching the requested format.
    *
    * @access public
    *
    * @param array $options The "options" portion of the token (second
    * element).
    *
    * @return string The text rendered from the token options.
    *
    */

    public function token($options)
    {
        $img = '{img src="';
        if (!empty($this->conf['prefix'])) {
            $img .= $this->conf['prefix'];
        }
        $img .= $options['src'] . '"';

        if (isset($options['attr']) && is_array($options['attr'])) {
            foreach ($options['attr'] as $var => $val) {
                $img .= ' ' . $var . '="' . $val . '"';
            }
        }
        $img .= '}';
        return $img;
    }
}
