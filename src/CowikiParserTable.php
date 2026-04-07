<?php

namespace Horde\Text\Wiki;

/**
*
* Parses for table markup.
*
* @category Text
*
* @package Text_Wiki
*
* @author Paul M. Jones <pmjones@php.net>
*
* @license LGPL
*
* @version $Id$
*
*/

/**
*
* Parses for table markup.
*
* This class implements a Text_Wiki_Parse to find source text marked as a
* set of table rows, where a line start and ends with double-pipes (||)
* and uses double-pipes to separate table cells.  The rows must be on
* sequential lines (no blank lines between them) -- a blank line
* indicates the beginning of a new table.
*
* @category Text
*
* @package Text_Wiki
*
* @author Paul M. Jones <pmjones@php.net>
*
*/

class CowikiParserTable extends WikiParserBase
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

    public $regex = '/\n<table( [^>]*)?>((?:[^<]|<(?!\/table>))*?)<\/table>\n/Us';


    /**
    *
    * Generates a replacement for the matched text.
    *
    * Token options are:
    *
    * 'type' =>
    *     'table_start' : the start of a bullet list
    *     'table_end'   : the end of a bullet list
    *     'row_start' : the start of a number list
    *     'row_end'   : the end of a number list
    *     'cell_start'   : the start of item text (bullet or number)
    *     'cell_end'     : the end of item text (bullet or number)
    *
    * 'cols' => the number of columns in the table (for 'table_start')
    *
    * 'rows' => the number of rows in the table (for 'table_start')
    *
    * 'span' => column span (for 'cell_start')
    *
    * 'attr' => column attribute flag (for 'cell_start')
    *
    * @access public
    *
    * @param array $matches The array of matches from parse().
    *
    * @return A series of text and delimited tokens marking the different
    * table elements and cell text.
    *
    */

    public function process($matches)
    {
        if (strlen(trim($matches[1]))) {
            $attr = $this->getAttrs(trim($matches[1]));
        } else {
            $attr = [];
        }

        // our eventual return value
        $return = '';

        // the number of columns in the table
        $num_cols = 0;

        // the number of rows in the table
        $num_rows = 0;

        // Extract all <tr> rows from the table content
        $table_content = $matches[2];

        // First try HTML format with proper <tr>...</tr> pairs
        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $table_content, $row_matches);

        // If no matches, try pipe format where <tr> is just a line marker
        if (empty($row_matches[0])) {
            // Split by <tr> markers, each line after <tr> is a row
            $lines = preg_split('/\n?<tr[^>]*>/i', $table_content);
            // Remove first empty element (before first <tr>)
            array_shift($lines);
            // Treat each line as row content
            $row_matches = [[], $lines];
        }

        // loop through each row
        foreach ($row_matches[1] as $row_content) {
            // Detect format: HTML (with <th>/<td> tags) or pipe-delimited
            $cells = $this->detectAndParseCells($row_content);

            if (empty($cells)) {
                continue;
            }

            // increase the row count
            ++$num_rows;

            // start a new row
            $return .= $this->wiki->addToken(
                $this->rule,
                ['type' => 'row_start']
            );

            // Update column count
            if (count($cells) > $num_cols) {
                $num_cols = count($cells);
            }

            // Process each cell
            foreach ($cells as $cell) {
                // start a new cell...
                $return .= $this->wiki->addToken(
                    $this->rule,
                    [
                        'type' => 'cell_start',
                        'attr' => $cell['attr'],
                        'span' => $cell['span'],
                    ]
                );

                // ...add the content...
                $return .= trim($cell['content']);

                // ...and end the cell.
                $return .= $this->wiki->addToken(
                    $this->rule,
                    [
                        'type' => 'cell_end',
                        'attr' => $cell['attr'],
                        'span' => $cell['span'],
                    ]
                );
            }

            // end the row
            $return .= $this->wiki->addToken(
                $this->rule,
                ['type' => 'row_end']
            );
        }

        // wrap the return value in start and end tokens
        $return
            = $this->wiki->addToken(
                $this->rule,
                [
                    'type' => 'table_start',
                    'rows' => $num_rows,
                    'cols' => $num_cols,
                    'attr' => $attr,
                ]
            )
            . $return
            . $this->wiki->addToken(
                $this->rule,
                [
                    'type' => 'table_end',
                ]
            );

        // we're done!
        return $return;
    }

    /**
     * Detect row format and parse cells accordingly
     *
     * @param string $row_content Content between <tr> and </tr>
     * @return array Array of cell structures
     */
    private function detectAndParseCells($row_content)
    {
        $trimmed = trim($row_content);

        // Check for HTML format: contains <th> or <td> tags
        if (preg_match('/<(th|td)[\s>]/', $trimmed)) {
            return $this->parseHtmlCells($row_content);
        }

        // Check for pipe format: starts with | or contains | without HTML tags
        if (preg_match('/^\|/', $trimmed) ||
            (strpos($trimmed, '|') !== false && !preg_match('/<[^>]+>/', $trimmed))) {
            return $this->parsePipeCells($row_content);
        }

        // Empty or unknown format
        return [];
    }

    /**
     * Parse HTML-style cells with <th> and <td> tags
     *
     * @param string $row_content Row content
     * @return array Array of cell structures
     */
    private function parseHtmlCells($row_content)
    {
        preg_match_all('/<(th|td)([^>]*)>(.*?)<\/\1>/is', $row_content, $cell_matches);

        $cells = [];
        for ($i = 0; $i < count($cell_matches[0]); ++$i) {
            $cell_type = $cell_matches[1][$i]; // 'th' or 'td'
            $cell_attrs = trim($cell_matches[2][$i]);
            $cell_content = $cell_matches[3][$i];

            // Parse colspan if present
            $span = 1;
            if (preg_match('/colspan=["\']?(\d+)["\']?/i', $cell_attrs, $span_match)) {
                $span = (int) $span_match[1];
            }

            // Determine cell attribute
            // For header cells, use 'header'; for data cells, check for align
            if ($cell_type === 'th') {
                $cell_attr = 'header';
            } else {
                // Check for text-align in style attribute or align attribute
                $cell_attr = '';
                if (preg_match('/\balign=["\']?(left|center|right)["\']?/i', $cell_attrs, $align_match)) {
                    $cell_attr = strtolower($align_match[1]);
                } elseif (preg_match('/text-align\s*:\s*(left|center|right)/i', $cell_attrs, $align_match)) {
                    $cell_attr = strtolower($align_match[1]);
                }
            }

            $cells[] = [
                'content' => $cell_content,
                'attr' => $cell_attr,
                'span' => $span,
            ];
        }

        return $cells;
    }

    /**
     * Parse pipe-delimited cells (PEAR Text_Wiki2 format)
     *
     * @param string $row_content Row content like "|Cell 1|Cell 2|"
     * @return array Array of cell structures
     */
    private function parsePipeCells($row_content)
    {
        // Split by pipe
        $parts = explode('|', $row_content);

        $cells = [];

        // PEAR logic: iterate from 1 to count-1
        $last = count($parts);

        for ($i = 1; $i < $last; ++$i) {
            $part = trim($parts[$i]);

            // Empty cell at this position
            if ($part === '') {
                // Skip - this will be counted as span for previous cell
                continue;
            }

            // Non-empty cell - count consecutive empty cells after it
            // Stop at last-1 to exclude trailing empty after final |
            $span = 1;
            for ($j = $i + 1; $j < $last - 1; ++$j) {
                if (trim($parts[$j]) === '') {
                    $span++;
                } else {
                    // Hit another non-empty cell, stop counting
                    break;
                }
            }

            $cells[] = [
                'content' => $part,
                'attr' => '',  // Pipe format doesn't distinguish headers
                'span' => $span,
            ];
        }

        return $cells;
    }
}
