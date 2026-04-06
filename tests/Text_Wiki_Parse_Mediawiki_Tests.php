<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once 'Text/Wiki/Mediawiki.php';
require_once 'Text/Wiki/Parse/Mediawiki/Break.php';
require_once 'Text/Wiki/Parse/Mediawiki/Code.php';
require_once 'Text/Wiki/Parse/Mediawiki/Comment.php';
require_once 'Text/Wiki/Parse/Mediawiki/Deflist.php';
require_once 'Text/Wiki/Parse/Mediawiki/Emphasis.php';
require_once 'Text/Wiki/Parse/Mediawiki/Heading.php';
require_once 'Text/Wiki/Parse/Mediawiki/List.php';
require_once 'Text/Wiki/Parse/Mediawiki/Newline.php';
require_once 'Text/Wiki/Parse/Mediawiki/Preformatted.php';
require_once 'Text/Wiki/Parse/Mediawiki/Raw.php';
require_once 'Text/Wiki/Parse/Mediawiki/Redirect.php';
require_once 'Text/Wiki/Parse/Mediawiki/Subscript.php';
require_once 'Text/Wiki/Parse/Mediawiki/Superscript.php';
require_once 'Text/Wiki/Parse/Mediawiki/Table.php';
require_once 'Text/Wiki/Parse/Mediawiki/Tt.php';
require_once 'Text/Wiki/Parse/Mediawiki/Url.php';
require_once 'Text/Wiki/Parse/Mediawiki/Wikilink.php';

// default parse rules used by Mediawiki parser
require_once 'Text/Wiki/Parse/Default/Horiz.php';

class Text_Wiki_Parse_Mediawiki_AllTests extends PHPUnit_Framework_TestSuite
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Text_Wiki_Render_Mediawiki_TestSuite');
        $suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Break_Test');
        /*$suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Code_Test');
        $suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Comment_Test');*/
        $suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Deflist_Test');
        $suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Emphasis_Test');
        $suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Heading_Test');
        $suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Horiz_Test');
        $suite->addTestSuite('Text_Wiki_Parse_Mediawiki_List_Test');
        //$suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Newline_Test');
        $suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Preformatted_Test');
        $suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Raw_Test');
        $suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Redirect_Test');
        /*$suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Subscript_Test');
        $suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Superscript_Test');*/
        $suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Table_Test');
        //$suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Tt_Test');
        $suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Url_Test');
        $suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Wikilink_Test');

        return $suite;
    }

}

/**
 * @coversNothing
 */
class Text_Wiki_Parse_Mediawiki_SetUp_Tests extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $obj = Text_Wiki::factory('Mediawiki');
        $testClassName = get_class($this);
        $ruleName = preg_replace('/Text_Wiki_Parse_Mediawiki_(.+?)_Test/', '\\1', $testClassName);
        $this->className = 'Text_Wiki_Parse_' . $ruleName;
        $this->t = new $this->className($obj);

        if (file_exists(dirname(__FILE__) . '/fixtures/mediawiki_syntax_to_test_' . strtolower($ruleName) . '.txt')) {
            $this->fixture = file_get_contents(dirname(__FILE__) . '/fixtures/mediawiki_syntax_to_test_' . strtolower($ruleName) . '.txt');
        } else {
            $this->fixture = file_get_contents(dirname(__FILE__) . '/fixtures/mediawiki_syntax.txt');
        }

        preg_match_all($this->t->regex, $this->fixture, $this->matches);
    }

}

/**
 * @coversNothing
 */
class Text_Wiki_Parse_Mediawiki_Break_Test extends Text_Wiki_Parse_Mediawiki_SetUp_Tests
{
    public function testMediawikiParseBreakProcess()
    {
        $matches1 = [0 => '<br />'];
        $matches2 = [0 => '<br   />'];

        $this->assertRegExp('/\d+?/', $this->t->process($matches1));
        $this->assertRegExp('/\d+?/', $this->t->process($matches2));

        $tokens = [0 => [0 => 'Break', 1 => []],
            1 => [0 => 'Break', 1 => []]];

        $this->assertEquals(array_values($tokens), array_values($this->t->wiki->tokens));
    }

    public function testMediawikiParseBreakRegex()
    {
        $expectedResult = [0 => [0 => '<br />', 1 => '<br   />']];
        $this->assertEquals($expectedResult, $this->matches);
    }

}

/**
 * @coversNothing
 */
class Text_Wiki_Parse_Mediawiki_Deflist_Test extends Text_Wiki_Parse_Mediawiki_SetUp_Tests
{
    public function testMediawikiParseDeflistProcess()
    {
        $matches1 = [
            0 => "\n;Definition lists\n;item : definition\n;semicolon plus term\n:colon plus definition\n",
            1 => ";Definition lists\n;item : definition\n;semicolon plus term\n:colon plus definition\n",
        ];

        $this->assertRegExp("/\d+?\d+?Definition lists\d+?\d+?item\d+?\d+?definition\d+?\d+?semicolon plus term\d+?\d+?colon plus definition\d+?\d+?/", $this->t->process($matches1));

        $tokens = [
            2 => [0 => 'Deflist', 1 => ['type' => 'list_start', 'level' => 0]],
            3 => [0 => 'Deflist', 1 => ['type' => 'term_start', 'level' => 1, 'count' => 0, 'first' => true]],
            4 => [0 => 'Deflist', 1 => ['type' => 'term_end', 'level' => 1, 'count' => 0]],
            5 => [0 => 'Deflist', 1 => ['type' => 'term_start', 'level' => 1, 'count' => 1, 'first' => false]],
            6 => [0 => 'Deflist', 1 => ['type' => 'term_end', 'level' => 1, 'count' => 1]],
            7 => [0 => 'Deflist', 1 => ['type' => 'narr_start', 'level' => 1, 'count' => 2, 'first' => false]],
            8 => [0 => 'Deflist', 1 => ['type' => 'narr_end', 'level' => 1, 'count' => 2]],
            9 => [0 => 'Deflist', 1 => ['type' => 'term_start', 'level' => 1, 'count' => 3, 'first' => false]],
            10 => [0 => 'Deflist', 1 => ['type' => 'term_end', 'level' => 1, 'count' => 3]],
            11 => [0 => 'Deflist', 1 => ['type' => 'narr_start', 'level' => 1, 'count' => 4, 'first' => false]],
            12 => [0 => 'Deflist', 1 => ['type' => 'narr_end', 'level' => 1, 'count' => 4]],
            13 => [0 => 'Deflist', 1 => ['type' => 'list_end', 'level' => 0]],
        ];

        $this->assertEquals(array_values($tokens), array_values($this->t->wiki->tokens));
    }

    public function testMediawikiParseDeflistRegex()
    {
        $expectedResult = [
            0 => [
                0 => "
;Definition lists
;item : definition
;semicolon plus term
:colon plus definition
",
            ],
            1 => [
                0 => ";Definition lists
;item : definition
;semicolon plus term
:colon plus definition
",
            ],
        ];
        $this->assertEquals($expectedResult, $this->matches);
    }

}


/**
 * @coversNothing
 */
class Text_Wiki_Parse_Mediawiki_Emphasis_Test extends PHPUnit_Framework_TestCase
{
    public function testMediawikiParseEmphasisParse()
    {
        $obj = $this->getMock('Text_Wiki_Parse_Emphasis', ['process'], [], 'Text_Wiki_Parse_Emphasis_Parse_Mock', false);
        $obj->wiki = $this->getMock('Text_Wiki');
        $obj->wiki->source = file_get_contents(dirname(__FILE__) . '/fixtures/mediawiki_syntax.txt');

        $lines = explode("\n", $obj->wiki->source);
        $i = count($lines);
        $obj->expects($this->exactly($i))->method('process');

        $obj->parse();
    }

    public function testMediawikiParseEmphasisProcess()
    {
        $textwiki = Text_Wiki::factory('Mediawiki');
        $obj = new Text_Wiki_Parse_Emphasis($textwiki);

        $lines = [
            "'''Bold text''' and ''italic text'' and even '''''bold italic text'''''",
            "'''Bold text''' and ''italic text'' and even '''''bold italic text''''' some text '''bold then ''italic'' then bold''' more text ''italic then '''bold''' then italic again'' some text '''''bold and italic'''''",
            "'''''bold and italic''' and italic''",
            "''italic and '''bold and italic'''''",
        ];

        foreach ($lines as $line) {
            $obj->process($line);
        }

        $expectedResult = [
            14 => [0 => 'Strong', 1 => ['type' => 'start']],
            15 => [0 => 'Strong', 1 => ['type' => 'end']],
            16 => [0 => 'Emphasis', 1 => ['type' => 'start']],
            17 => [0 => 'Emphasis', 1 => ['type' => 'end']],
            18 => [0 => 'Emphasis', 1 => ['type' => 'start']],
            19 => [0 => 'Strong', 1 => ['type' => 'start']],
            20 => [0 => 'Strong',1 => ['type' => 'end']],
            21 => [0 => 'Emphasis', 1 => ['type' => 'end']],
            22 => [0 => 'Strong', 1 => ['type' => 'start']],
            23 => [0 => 'Strong', 1 => ['type' => 'end']],
            24 => [0 => 'Emphasis', 1 => ['type' => 'start']],
            25 => [0 => 'Emphasis', 1 => ['type' => 'end']],
            26 => [0 => 'Emphasis', 1 => ['type' => 'start']],
            27 => [0 => 'Strong', 1 => ['type' => 'start']],
            28 => [0 => 'Strong', 1 => ['type' => 'end']],
            29 => [0 => 'Emphasis', 1 => ['type' => 'end']],
            30 => [0 => 'Strong', 1 => ['type' => 'start']],
            31 => [0 => 'Emphasis', 1 => ['type' => 'start']],
            32 => [0 => 'Emphasis', 1 => ['type' => 'end']],
            33 => [0 => 'Strong', 1 => ['type' => 'end']],
            34 => [0 => 'Emphasis', 1 => ['type' => 'start']],
            35 => [0 => 'Strong', 1 => ['type' => 'start']],
            36 => [0 => 'Strong', 1 => ['type' => 'end']],
            37 => [0 => 'Emphasis', 1 => ['type' => 'end']],
            38 => [0 => 'Emphasis', 1 => ['type' => 'start']],
            39 => [0 => 'Strong', 1 => ['type' => 'start']],
            40 => [0 => 'Strong', 1 => ['type' => 'end']],
            41 => [0 => 'Emphasis', 1 => ['type' => 'end']],
            42 => [0 => 'Emphasis', 1 => ['type' => 'start']],
            43 => [0 => 'Strong', 1 => ['type' => 'start']],
            44 => [0 => 'Strong', 1 => ['type' => 'end']],
            45 => [0 => 'Emphasis', 1 => ['type' => 'end']],
            46 => [0 => 'Emphasis', 1 => ['type' => 'start']],
            47 => [0 => 'Strong', 1 => ['type' => 'start']],
            48 => [0 => 'Strong', 1 => ['type' => 'end']],
            49 => [0 => 'Emphasis', 1 => ['type' => 'end']],
        ];

        $this->assertEquals(array_values($expectedResult), array_values($obj->wiki->tokens));
    }

}

/**
 * @coversNothing
 */
class Text_Wiki_Parse_Mediawiki_Heading_Test extends Text_Wiki_Parse_Mediawiki_SetUp_Tests
{
    public function testMediawikiParseHeadingProcess()
    {
        $matches1 = [0 => "======Level 6 heading======", 1 => '======', 2 => 'Level 6 heading'];
        $matches2 = [0 => "=Level 1 heading=", 1 => '=', 2 => 'Level 1 heading'];
        $matches3 = [0 => "==Level 2 heading==", 1 => '==', 2 => 'Level 2 heading'];

        $this->assertRegExp("/\d+?Level 6 heading\d+?\n/", $this->t->process($matches1));
        $this->assertRegExp("/\d+?Level 1 heading\d+?\n/", $this->t->process($matches2));
        $this->assertRegExp("/\d+?Level 2 heading\d+?\n/", $this->t->process($matches3));

        $tokens = [
            0 => [0 => 'Heading', 1 => ['type' => 'start', 'level' => 6, 'text' => 'Level 6 heading', 'id' => 'toc0']],
            1 => [0 => 'Heading', 1 => ['type' => 'end', 'level' => 6]],
            2 => [0 => 'Heading', 1 => ['type' => 'start', 'level' => 1, 'text' => 'Level 1 heading', 'id' => 'toc1']],
            3 => [0 => 'Heading', 1 => ['type' => 'end', 'level' => 1]],
            4 => [0 => 'Heading', 1 => ['type' => 'start', 'level' => 2, 'text' => 'Level 2 heading', 'id' => 'toc2']],
            5 => [0 => 'Heading', 1 => ['type' => 'end', 'level' => 2]],
        ];

        $this->assertEquals(array_values($tokens), array_values($this->t->wiki->tokens));
    }

    public function testMediawikiParseHeadingRegex()
    {
        $expectedResult = [
            0 => [0 => "=Level 1 heading=", 1 => "==Level 2 heading==", 2 => "==Level 2 heading==", 3 => "===Level 3 heading===", 4 => "====Level 4 heading====", 5 => "===Level 3 heading===", 6 => "===Level 3 heading===", 7 => "=====Level 5 heading=====", 8 => "======Level 6 heading======"],
            1 => [0 => '=', 1 => '==', 2 => '==', 3 => '===', 4 => '====', 5 => '===', 6 => '===', 7 => '=====', 8 => '======'],
            2 => [0 => 'Level 1 heading', 1 => 'Level 2 heading', 2 => 'Level 2 heading', 3 => 'Level 3 heading', 4 => 'Level 4 heading', 5 => 'Level 3 heading', 6 => 'Level 3 heading', 7 => 'Level 5 heading', 8 => 'Level 6 heading'],
        ];
        $this->assertEquals($expectedResult, $this->matches);
    }

}

// Mediawiki parse uses horiz rule from default parser
/**
 * @coversNothing
 */
class Text_Wiki_Parse_Mediawiki_Horiz_Test extends Text_Wiki_Parse_Mediawiki_SetUp_Tests
{
    public function testMediawikiParseHorizProcess()
    {
        $matches1 = [0 => '----', 1 => '----'];
        $matches2 = [0 => '------', 1 => '------'];

        $this->assertRegExp("/\d+?/", $this->t->process($matches1));
        $this->assertRegExp("/\d+?/", $this->t->process($matches2));

        $tokens = [
            0 => [0 => 'Horiz', []],
            1 => [0 => 'Horiz', []],
        ];

        $this->assertEquals(array_values($tokens), array_values($this->t->wiki->tokens));
    }

    public function testMediawikiParseHeadingRegex()
    {
        $expectedResult = [
            0 => [0 => '----', 1 => '------'],
            1 => [0 => '----', 1 => '------'],
        ];
        $this->assertEquals($expectedResult, $this->matches);
    }

}

/**
 * @coversNothing
 */
class Text_Wiki_Parse_Mediawiki_List_Test extends Text_Wiki_Parse_Mediawiki_SetUp_Tests
{
    public function testMediawikiParseListProcess()
    {
        $matches1 = [
            1 => "
* List example
** List example
**# List example
**# List example
*** List example
**** List example
** List example
",
            2 => "* List example
** List example
**# List example
**# List example
*** List example
**** List example
** List example
",
        ];

        $this->assertRegExp("/\d+?\d+? List example\d+?\d+?\d+? List example\d+?\d+?\d+? List example\d+?\d+? List example\d+?\d+? List example\d+?\d+?\d+? List example\d+?\d+?\d+?\d+? List example\d+?\d+?\d+?/", $this->t->process($matches1));

        $tokens = [
            432 => [0 => 'List', 1 => ['type' => 'bullet_list_start', 'level' => 1]],
            433 => [0 => 'List', 1 => ['type' => 'bullet_item_start', 'level' => 1, 'count' => 0, 'first' => true]],
            434 => [0 => 'List', 1 => ['type' => 'bullet_item_end', 'level' => 1, 'count' => 0]],
            435 => [0 => 'List', 1 => ['type' => 'bullet_list_start', 'level' => 2]],
            436 => [0 => 'List', 1 => ['type' => 'bullet_item_start', 'level' => 2, 'count' => 0, 'first' => false]],
            437 => [0 => 'List', 1 => ['type' => 'bullet_item_end', 'level' => 2, 'count' => 0]],
            438 => [0 => 'List', 1 => ['type' => 'number_list_start', 'level' => 3]],
            439 => [0 => 'List', 1 => ['type' => 'number_item_start', 'level' => 3, 'count' => 0, 'first' => false]],
            440 => [0 => 'List', 1 => ['type' => 'number_item_end', 'level' => 3, 'count' => 0]],
            441 => [0 => 'List', 1 => ['type' => 'number_item_start', 'level' => 3, 'count' => 1, 'first' => false]],
            442 => [0 => 'List', 1 => ['type' => 'number_item_end', 'level' => 3, 'count' => 1]],
            443 => [0 => 'List', 1 => ['type' => 'bullet_item_start', 'level' => 3, 'count' => 2, 'first' => false]],
            444 => [0 => 'List', 1 => ['type' => 'bullet_item_end', 'level' => 3, 'count' => 2]],
            445 => [0 => 'List', 1 => ['type' => 'bullet_list_start', 'level' => 4]],
            446 => [0 => 'List', 1 => ['type' => 'bullet_item_start', 'level' => 4, 'count' => 0, 'first' => false]],
            447 => [0 => 'List', 1 => ['type' => 'bullet_item_end', 'level' => 4, 'count' => 0]],
            448 => [0 => 'List', 1 => ['type' => 'bullet_list_end', 'level' => 3]],
            449 => [0 => 'List', 1 => ['type' => 'number_list_end', 'level' => 2]],
            450 => [0 => 'List', 1 => ['type' => 'bullet_item_start', 'level' => 2, 'count' => 1, 'first' => false]],
            451 => [0 => 'List', 1 => ['type' => 'bullet_item_end', 'level' => 2, 'count' => 1]],
            452 => [0 => 'List', 1 => ['type' => 'bullet_list_end', 'level' => 1]],
            453 => [0 => 'List', 1 => ['type' => 'bullet_list_end', 'level' => 0]],
        ];

        $this->assertEquals(array_values($tokens), array_values($this->t->wiki->tokens));
    }

    public function testMediawikiParseListRegex()
    {
        $expectedResult = [
            0 => [
                0 => "
* List
* List
** List
** List
*** List
* List
* List
",
                1 => "
# List
# List
## List
## List
### List
# List
# List
",
                2 => "
* List example
** List example
**# List example
**# List example
*** List example
**** List example
*# List example
*# List example
** List example
* List example
** List example
** List example
**# List example
",
            ],
            1 => [
                0 => "* List
* List
** List
** List
*** List
* List
* List
",
                1 => "# List
# List
## List
## List
### List
# List
# List
",
                2 => "* List example
** List example
**# List example
**# List example
*** List example
**** List example
*# List example
*# List example
** List example
* List example
** List example
** List example
**# List example
",
            ],
        ];

        $this->assertEquals($expectedResult, $this->matches);
    }

}

/**
 * @coversNothing
 */
class Text_Wiki_Parse_Mediawiki_Preformatted_Test extends Text_Wiki_Parse_Mediawiki_SetUp_Tests
{
    public function testMediawikiParsePreformattedProcess()
    {
        $matches1 = [0 => "<pre>pre tag without line break</pre>", 1 => 'pre tag without line break'];
        // not sure why Text_Wiki_Parse_Preformatted uses $matches[2]
        $matches2 = [0 => "<pre>pre tag without line break</pre>", 1 => 'pre tag without line break', 2 => 'some text'];

        $this->assertRegExp("/\d+?/", $this->t->process($matches1));
        $this->assertRegExp("/\d+?/", $this->t->process($matches2));

        $tokens = [
            0 => [0 => 'Preformatted', 1 => ['text' => 'pre tag without line break']],
            1 => [0 => 'Preformatted', 1 => ['text' => 'some text']],
        ];

        $this->assertEquals(array_values($tokens), array_values($this->t->wiki->tokens));
    }

    public function testMediawikiParsePreformattedRegex()
    {
        $expectedResult = [
            0 => [
                0 => "<pre>
The pre tag ignores [[Wiki]] ''markup''.
It also doesn't     reformat text.
It still interprets special characters:
 &amp;rarr;
</pre>",
                1 => "<pre>pre tag without line break</pre>",
                2 => "<pre>some ''text'' without '''wiki''' parsing</pre>",
            ],
            1 => [
                0 => "The pre tag ignores [[Wiki]] ''markup''.
It also doesn't     reformat text.
It still interprets special characters:
 &amp;rarr;",
                1 => "pre tag without line break",
                2 => "some ''text'' without '''wiki''' parsing",
            ],
            2 => [0 => '', 1 => '', 2 => ''],
        ];

        $this->assertEquals($expectedResult, $this->matches);
    }

}

/**
 * @coversNothing
 */
class Text_Wiki_Parse_Mediawiki_Raw_Test extends Text_Wiki_Parse_Mediawiki_SetUp_Tests
{
    public function testMediawikiParseRawProcess()
    {
        $matches1 = [0 => "<nowiki>nowiki tag without break line</nowiki>", 1 => 'nowiki tag without line break'];

        $this->assertRegExp("/\d+?/", $this->t->process($matches1));

        $tokens = [
            0 => [0 => 'Raw', 1 => ['text' => 'nowiki tag without line break']],
        ];

        $this->assertEquals(array_values($tokens), array_values($this->t->wiki->tokens));
    }

    public function testMediawikiParseRawRegex()
    {
        $expectedResult = [
            0 => [
                0 => "<nowiki>
The nowiki tag ignores [[Wiki]] ''markup''.
It reformats text by removing newlines 
and multiple spaces.
It still interprets special
characters: &rarr;
</nowiki>",
                1 => "<nowiki>''ignores markup''</nowiki>",
            ],
            1 => [
                0 => "The nowiki tag ignores [[Wiki]] ''markup''.
It reformats text by removing newlines 
and multiple spaces.
It still interprets special
characters: &rarr;",
                1 => "''ignores markup''",
            ],
        ];

        $this->assertEquals($expectedResult, $this->matches);
    }

}

/**
 * @coversNothing
 */
class Text_Wiki_Parse_Mediawiki_Redirect_Test extends Text_Wiki_Parse_Mediawiki_SetUp_Tests
{
    public function testMediawikiParseRedirectProcess()
    {
        $matches1 = [0 => "#REDIRECT [[Some page name]]", 1 => 'Some page name'];
        $matches2 = [0 => "#redirect [[Other page name]]", 1 => 'Other page name'];

        $this->assertRegExp("/\d+?Some page name\d+?/", $this->t->process($matches1));
        $this->assertRegExp("/\d+?Other page name\d+?/", $this->t->process($matches2));

        $tokens = [
            0 => [0 => 'Redirect', 1 => ['type' => 'start', 'text' => 'Some page name']],
            1 => [0 => 'Redirect', 1 => ['type' => 'end']],
            2 => [0 => 'Redirect', 1 => ['type' => 'start', 'text' => 'Other page name']],
            3 => [0 => 'Redirect', 1 => ['type' => 'end']],
        ];

        $this->assertEquals(array_values($tokens), array_values($this->t->wiki->tokens));
    }

    public function testMediawikiParseRedirectRegex()
    {
        $expectedResult = [
            0 => [0 => "#REDIRECT [[Some page name]]", 1 => "#redirect [[Other page name]]"],
            1 => [0 => 'Some page name', 1 => 'Other page name'],
        ];
        $this->assertEquals($expectedResult, $this->matches);
    }

}

/**
 * @coversNothing
 */
class Text_Wiki_Parse_Mediawiki_Table_Test extends Text_Wiki_Parse_Mediawiki_SetUp_Tests
{
    public function testMediawikiParseTableProcess()
    {

        $matches = [
            0 => '{| 
| A || B
|- 
| C || D 
|}',
            1 => ' 
',
            2 => '',
            3 => '| A || B
|- 
| C || D 
',
        ];

        $this->assertRegExp("/\d+?\d+?\d+? A \d+?\d+? B\d+?\d+?\d+?\d+? C \d+?\d+? D \d+?\d+?\d+?/", $this->t->process($matches));

        $tokens = [
            487 => [0 => 'Table', 1 => ['type' => 'cell_start', 'attr' => '', 'span' => 1, 'rowspan' => 1, 'order' => 0]],
            488 => [0 => 'Table', 1 => ['type' => 'cell_end', 'attr' => '', 'span' => 1, 'rowspan' => 1, 'order' => 0]],
            489 => [0 => 'Table', 1 => ['type' => 'cell_start', 'attr' => '', 'span' => 1, 'rowspan' => 1, 'order' => 1]],
            490 => [0 => 'Table', 1 => ['type' => 'cell_end', 'attr' => '', 'span' => 1, 'rowspan' => 1, 'order' => 1]],
            491 => [0 => 'Table', 1 => ['type' => 'row_start', 'order' => 0, 'cols' => 2]],
            492 => [0 => 'Table', 1 => ['type' => 'row_end', 'order' => 0, 'cols' => 2]],
            493 => [0 => 'Table', 1 => ['type' => 'cell_start', 'attr' => '', 'span' => 1, 'rowspan' => 1, 'order' => 0]],
            494 => [0 => 'Table', 1 => ['type' => 'cell_end', 'attr' => '', 'span' => 1, 'rowspan' => 1, 'order' => 0]],
            495 => [0 => 'Table', 1 => ['type' => 'cell_start', 'attr' => '', 'span' => 1, 'rowspan' => 1, 'order' => 1]],
            496 => [0 => 'Table', 1 => ['type' => 'cell_end', 'attr' => '', 'span' => 1, 'rowspan' => 1, 'order' => 1]],
            497 => [0 => 'Table', 1 => ['type' => 'row_start', 'order' => 1, 'cols' => 2]],
            498 => [0 => 'Table', 1 => ['type' => 'row_end', 'order' => 1, 'cols' => 2]],
            499 => [0 => 'Table', 1 => ['type' => 'table_start', 'level' => 0, 'rows' => 2, 'cols' => 2]],
            500 => [0 => 'Table', 1 => ['type' => 'table_end', 'level' => 0, 'rows' => 2, 'cols' => 2]],
        ];

        $this->assertEquals(array_values($tokens), array_values($this->t->wiki->tokens));
    }

    public function testMediawikiParseTableRegex()
    {

        $expectedResult = [
            0 => [
                0 => "{| 
| A || B
|- 
| C || D 
|}",
            ],
            1 => [0 => " \n"],
            2 => [0 => ''],
            3 => [
                0 => "| A || B
|- 
| C || D 
",
            ],
            4 => [
                0 => '',
            ],
        ];

        $this->assertEquals($expectedResult, $this->matches);
    }

}


/**
 * @coversNothing
 */
class Text_Wiki_Parse_Mediawiki_Wikilink_Test extends Text_Wiki_Parse_Mediawiki_SetUp_Tests
{
    public function testMediawikiParseWikilinkProcessWithSpaceUnderscoreFalse()
    {
        $this->t->conf['spaceUnderscore'] = false;

        $matches1 = [0 => '[[convallis elementum]]', 1 => '', 2 => '', 3 => 'convallis elementum', 4 => '', 5 => '', 6 => ''];
        $matches2 = [0 => '[[Etiam]]', 1 => '', 2 => '', 3 => 'Etiam', 4 => '', 5 => '', 6 => ''];
        $matches3 = [0 => '[[pt:Language link]]', 1 => '', 2 => 'pt:', 3 => 'Language link', 4 => '', 5 => '', 6 => ''];
        $matches4 = [0 => '[[Image:some image]]', 1 => '', 2 => 'Image:', 3 => 'some image', 4 => '', 5 => '', 6 => ''];
        $matches5 = [0 => '[[Etiam|description text]]', 1 => '', 2 => '', 3 => 'Etiam', 4 => '', 5 => 'description text', 6 => ''];

        $this->assertRegExp("/\d+?/", $this->t->process($matches1));
        $this->assertRegExp("/\d+?/", $this->t->process($matches2));
        $this->assertRegExp("/\d+?/", $this->t->process($matches3));
        $this->assertRegExp("/\d+?/", $this->t->process($matches4));
        $this->assertRegExp("/\d+?/", $this->t->process($matches5));

        $tokens = [
            0 => [0 => 'Wikilink', 1 => ['page' => 'convallis elementum', 'anchor' => '', 'text' => 'convallis elementum']],
            1 => [0 => 'Wikilink', 1 => ['page' => 'Etiam', 'anchor' => '', 'text' => 'Etiam']],
            2 => [0 => 'Wikilink', 1 => ['page' => 'pt:Language link', 'anchor' => '', 'text' => 'pt:Language link']],
            3 => [0 => 'Image', 1 => ['src' => 'some image', 'attr' => ['alt' => 'some image']]],
            4 => [0 => 'Wikilink', 1 => ['page' => 'Etiam', 'anchor' => '', 'text' => 'description text']],
        ];

        $this->assertEquals(array_values($tokens), array_values($this->t->wiki->tokens));
    }

    public function testMediawikiParseWikilinkProcessWithSpaceUnderscoreTrue()
    {
        $this->t->conf['spaceUnderscore'] = true;

        $matches1 = [0 => '[[convallis elementum]]', 1 => '', 2 => '', 3 => 'convallis elementum', 4 => '', 5 => '', 6 => ''];

        $this->assertRegExp("/\d+?/", $this->t->process($matches1));

        $tokens = [
            0 => [0 => 'Wikilink', 1 => ['page' => 'convallis_elementum', 'anchor' => '', 'text' => 'convallis elementum']],
        ];

        $this->assertEquals(array_values($tokens), array_values($this->t->wiki->tokens));
    }

    public function testMediawikiParseWikilinkRegex()
    {
        require_once(dirname(__FILE__) . '/fixtures/test_mediawiki_wikilink_expected_matches.php');
        global $expectedWikilinkMatches;
        $fixture = file_get_contents(dirname(__FILE__) . '/fixtures/mediawiki_syntax_to_test_wikilink.txt');
        preg_match_all($this->t->regex, $fixture, $matches);

        $this->assertEquals($expectedWikilinkMatches, $matches);
    }

}

/**
 * @coversNothing
 */
class Text_Wiki_Parse_Mediawiki_Url_Test extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $textWiki = new Text_Wiki('Mediawiki');
        $this->obj = new Text_Wiki_Parse_Url($textWiki);
    }

    public function testMediawikiParseUrlParse()
    {
        // for some weird reason I was unable to mock this class calling the constructor that is why to
        // test the regular expression I'm testing the tokens created (instead of testing many times each process function was called)
        $this->obj->wiki->source = file_get_contents(dirname(__FILE__) . '/fixtures/mediawiki_syntax.txt');
        $this->obj->parse();

        $tokens = [
            1 => [0 => 'Url', 1 => ['type' => 'descr', 'href' => 'http://www.example.com', 'text' => 'See the example site']],
            2 => [0 => 'Url', 1 => ['type' => 'descr', 'href' => 'http://exemple.com/index.php', 'text' => 'consectetur adipiscing']],
            3 => [0 => 'Url', 1 => ['type' => 'descr', 'href' => 'http://exemple.com/index.php#anchor', 'text' => 'Pellentesque']],
            4 => [0 => 'Url', 1 => ['type' => 'descr', 'href' => 'http://www.somelink.com/index.php', 'text' => 'http://www.somelink.com/index.php']],
            5 => [0 => 'Url', 1 => ['type' => 'inline', 'href' => 'http://example.com/index.php', 'text' => 'http://example.com/index.php']],
        ];

        $this->assertEquals(array_values($tokens), array_values($this->obj->wiki->tokens));
    }

    /*    public function testMediawikiParseUrlParseWithMocking()
        {
            // NOT WORKING: unable to mock the class Text_Wiki_Parse_Url using its constructor
            $textWiki = Text_Wiki::factory('Mediawiki');
            $obj = $this->getMock('Text_Wiki_Parse_Url',
                array('process', 'processWithoutProtocol', 'processInlineEmail', 'processFootnote', 'processOrdinary', 'processDescr'),
                array($textWiki),
            );
            $obj->expects($this->once())->method('process');
            $obj->expects($this->never())->method('processWithoutProtocol');
            $obj->expects($this->never())->method('processInlineEmail');
            $obj->expects($this->never())->method('processFootnote');
            $obj->expects($this->exactly(2))->method('processOrdinary');
            $obj->expects($this->exactly(5))->method('processDescr');
            $obj->wiki->source = file_get_contents(dirname(__FILE__) . '/fixtures/mediawiki_syntax.txt');
            $obj->parse();
        }*/

    public function testProcess()
    {
        $this->markTestIncomplete('Test incomplete');
    }

    public function testProcessWithoutProtocol()
    {
        $this->markTestIncomplete('Test incomplete');
    }

    public function testProcessInlineEmail()
    {
        $this->markTestIncomplete('Test incomplete');
    }

    public function testProcessFootnote()
    {
        $this->markTestIncomplete('Test incomplete');
    }

    public function testProcessOrdinary()
    {
        $this->markTestIncomplete('Test incomplete');
    }

    public function testProcessDescr()
    {
        $this->markTestIncomplete('Test incomplete');
    }
}
