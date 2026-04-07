<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\BBCode\Validator;

/**
 * Color attribute validator for CSS injection prevention
 *
 * Validates color values to prevent CSS injection attacks.
 * Allows hex colors (#RGB, #RRGGBB), named colors, and rgb() values.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class ColorAttributeValidator
{
    /**
     * Named colors (CSS3 color keywords)
     *
     * @var array<string>
     */
    private const NAMED_COLORS = [
        'black', 'silver', 'gray', 'white', 'maroon', 'red', 'purple',
        'fuchsia', 'green', 'lime', 'olive', 'yellow', 'navy', 'blue',
        'teal', 'aqua', 'orange', 'aliceblue', 'antiquewhite', 'aquamarine',
        'azure', 'beige', 'bisque', 'blanchedalmond', 'blueviolet', 'brown',
        'burlywood', 'cadetblue', 'chartreuse', 'chocolate', 'coral',
        'cornflowerblue', 'cornsilk', 'crimson', 'cyan', 'darkblue',
        'darkcyan', 'darkgoldenrod', 'darkgray', 'darkgreen', 'darkgrey',
        'darkkhaki', 'darkmagenta', 'darkolivegreen', 'darkorange',
        'darkorchid', 'darkred', 'darksalmon', 'darkseagreen',
        'darkslateblue', 'darkslategray', 'darkslategrey', 'darkturquoise',
        'darkviolet', 'deeppink', 'deepskyblue', 'dimgray', 'dimgrey',
        'dodgerblue', 'firebrick', 'floralwhite', 'forestgreen', 'gainsboro',
        'ghostwhite', 'gold', 'goldenrod', 'greenyellow', 'grey', 'honeydew',
        'hotpink', 'indianred', 'indigo', 'ivory', 'khaki', 'lavender',
        'lavenderblush', 'lawngreen', 'lemonchiffon', 'lightblue',
        'lightcoral', 'lightcyan', 'lightgoldenrodyellow', 'lightgray',
        'lightgreen', 'lightgrey', 'lightpink', 'lightsalmon',
        'lightseagreen', 'lightskyblue', 'lightslategray', 'lightslategrey',
        'lightsteelblue', 'lightyellow', 'limegreen', 'linen', 'magenta',
        'mediumaquamarine', 'mediumblue', 'mediumorchid', 'mediumpurple',
        'mediumseagreen', 'mediumslateblue', 'mediumspringgreen',
        'mediumturquoise', 'mediumvioletred', 'midnightblue', 'mintcream',
        'mistyrose', 'moccasin', 'navajowhite', 'oldlace', 'olivedrab',
        'orangered', 'orchid', 'palegoldenrod', 'palegreen', 'paleturquoise',
        'palevioletred', 'papayawhip', 'peachpuff', 'peru', 'pink', 'plum',
        'powderblue', 'rosybrown', 'royalblue', 'saddlebrown', 'salmon',
        'sandybrown', 'seagreen', 'seashell', 'sienna', 'skyblue',
        'slateblue', 'slategray', 'slategrey', 'snow', 'springgreen',
        'steelblue', 'tan', 'thistle', 'tomato', 'turquoise', 'violet',
        'wheat', 'whitesmoke', 'yellowgreen', 'rebeccapurple',
    ];

    /**
     * Validate a color attribute value
     *
     * Accepts:
     * - Hex colors: #RGB or #RRGGBB
     * - Named colors: red, blue, etc.
     * - RGB values: rgb(255, 0, 0)
     *
     * Rejects anything with semicolons, quotes, or other injection vectors.
     *
     * @param string $color Color value to validate
     *
     * @return bool True if color is safe, false otherwise
     */
    public function validate(string $color): bool
    {
        $color = trim($color);

        // Empty colors are invalid
        if ($color === '') {
            return false;
        }

        // Reject injection attempts (semicolons, quotes, etc.)
        if (preg_match('/[;"\']/', $color)) {
            return false;
        }

        // Hex colors: #RGB or #RRGGBB
        if (preg_match('/^#[0-9a-fA-F]{3}$/', $color)) {
            return true;
        }
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            return true;
        }

        // Named colors
        if (in_array(strtolower($color), self::NAMED_COLORS, true)) {
            return true;
        }

        // RGB values: rgb(R, G, B) where R, G, B are 0-255
        if (preg_match('/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/', $color, $matches)) {
            return (int) $matches[1] <= 255
                && (int) $matches[2] <= 255
                && (int) $matches[3] <= 255;
        }

        return false;
    }
}
