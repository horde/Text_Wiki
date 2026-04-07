<?php

namespace Horde\Text\Wiki;

class CowikiRendererTable extends WikiRendererBase
{
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
        switch ($options['type']) {

            case 'table_start':
                return "\n<table>\n";
                break;

            case 'table_end':
                return "</table>\n";
                break;

            case 'row_start':
                return "<tr>";
                break;

            case 'row_end':
                return "</tr>\n";
                break;

            case 'cell_start':
                // Determine if this is a header or data cell
                $tag = ($options['attr'] === 'header') ? 'th' : 'td';
                $attrs = '';

                // Add colspan if > 1
                if (isset($options['span']) && $options['span'] > 1) {
                    $attrs .= ' colspan="' . $options['span'] . '"';
                }

                // Add alignment if present and not a header
                if ($options['attr'] !== 'header' && !empty($options['attr'])) {
                    $attrs .= ' align="' . $options['attr'] . '"';
                }

                return "<$tag$attrs>";
                break;

            case 'cell_end':
                // Determine closing tag
                $tag = ($options['attr'] === 'header') ? 'th' : 'td';
                return "</$tag>";
                break;

            default:
                return '';

        }
    }
}
