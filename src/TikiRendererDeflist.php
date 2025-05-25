<?php

namespace HordeTextWiki;

class TikiRendererDeflist extends WikiRender
{
    /**
     * The last token type rendered
     *
     * @var string
     */
    public $last = '';

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
        $type = $options['type'];

        switch ($type) {

            case 'list_start':
                $output = "{DL()}\n";
                break;

            case 'list_end':
                $output = "{DL}\n\n";
                break;

            case 'term_start':
                // support definition item without narrative
                if ($this->last == 'term_end') {
                    $output = "\n";
                } else {
                    $output = '';
                }
                break;

            case 'term_end':
                $output = ": ";
                break;

            case 'narr_start':
                $output = '';
                break;

            case 'narr_end':
                $output = "\n";
                break;

            default:
                $output = '';

        }

        $this->last = $type;
        return $output;
    }
}
