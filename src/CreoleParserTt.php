<?php

namespace Horde\Text\Wiki;

/**
 *
 * Parses for monospaced inline text.
 *
 * @category Text
 *
 * @package Text_Wiki
 *
 * @author Tomaiuolo Michele <tomamic@yahoo.it>
 *
 * @license LGPL
 *
 * @version $Id$
 *
 */

class CreoleParserTt extends WikiParserBase
{
    /**
     *
     * The regular expression used to parse the source text and find
     * matches conforming to this rule.  Used by the parse() method.
     *
     * @access public
     *
     * @var string
     *
     * @see parse()
     *
     */

    public $regex = '/{{{(.*?)}}}(?!}|{{{)/';

    /**
     *
     * Generates a replacement for the matched text.  Token options are:
     *
     * 'type' => ['start'|'end'] The starting or ending point of the
     * monospaced text.  The text itself is encapsulated into a Raw token.
     *
     * @access public
     *
     * @param array $matches The array of matches from parse().
     *
     * @return string A token to be used as a placeholder
     * in the source text for the preformatted text.
     *
     */

    public function process($matches)
    {
        // remove the sequence }}}{{{
        $find = "/}}}{{{/";
        $replace = "";
        $matches[1] = preg_replace($find, $replace, $matches[1]);

        $start = $this->wiki->addToken(
            $this->rule,
            ['type' => 'start']
        );

        $raw = $this->wiki->addToken(
            'Raw',
            ['text' => $matches[1]]
        );

        $end = $this->wiki->addToken(
            $this->rule,
            ['type' => 'end']
        );

        return $start . $raw . $end;
    }
}
