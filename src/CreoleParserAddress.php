<?php

namespace Horde\Text\Wiki;

/**
 *
 * Parses for signatures.
 * This class implements a Text_Wiki rule to find sections of the source
 * text that are signatures. A signature is any line starting with exactly
 * two - signs.
 *
 * @category Text
 *
 * @package Text_Wiki
 *
 * @author Michele Tomaiuolo <tomamic@yahoo.it>
 *
 * @license LGPL
 *
 * @version $Id$
 *
 */

class CreoleParserAddress extends WikiParserBase
{
    /**
     *
     * The regular expression used to find source text matching this
     * rule.
     *
     * @access public
     *
     * @var string
     *
     */

    public $regex = '/^--([^-].*)$/m';

    /**
     *
     * Generates a token entry for the matched text. Token options are:
     *
     * 'start' => The starting point of the signature.
     *
     * 'end' => The ending point of the signature.
     *
     * @access public
     *
     * @param array &$matches The array of matches from parse().
     *
     * @return A delimited token number to be used as a placeholder in
     * the source text.
     *
     */

    public function process(&$matches)
    {
        $start = $this->wiki->addToken(
            $this->rule,
            ['type' => 'start']
        );

        $end = $this->wiki->addToken(
            $this->rule,
            ['type' => 'end']
        );

        return "\n" . $start . trim($matches[1]) . $end;
    }
}
