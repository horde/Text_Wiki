<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

// class to test the Text_Wiki::transform() with different wiki markups
/**
 * @coversNothing
 */
class MediawikiToTikiTest extends TestCase
{
    public function testTransformFromMediawikiToTiki()
    {
        $obj = TextWikiBase::factory('Mediawiki');
        $obj->parseConf['Wikilink']['spaceUnderscore'] = false;
        $source = file_get_contents(dirname(__FILE__, 2) . '/fixtures/test_mediawiki_to_tiki_source.txt');
        $expectedResult = file_get_contents(dirname(__FILE__, 2) . '/fixtures/test_mediawiki_to_tiki_output.txt');
        $this->assertEquals($expectedResult, $obj->transform($source, 'Tiki'));
    }
    // TODO: These tests fail due to extra newlines compared to the expected output.
    /*    public function testTransformFromMediawikiToTikiListSyntax()
        {
            $obj = TextWikiBase::factory('Mediawiki');
            $obj->parseConf['Wikilink']['spaceUnderscore'] = false;
            $source = file_get_contents(dirname(__FILE__, 2) . '/fixtures/test_mediawiki_to_tiki_lists_source.txt');
            $expectedResult = file_get_contents(dirname(__FILE__, 2) . '/fixtures/test_mediawiki_to_tiki_lists_output.txt');
            $this->assertEquals($expectedResult, $obj->transform($source, 'Tiki'));
        }

        public function testTransformFromMediawikiToTikiRedirectSyntax()
        {
            $obj = TextWikiBase::factory('Mediawiki');
            $obj->parseConf['Wikilink']['spaceUnderscore'] = false;
            $source = file_get_contents(dirname(__FILE__, 2) . '/fixtures/test_mediawiki_to_tiki_redirect_source.txt');
            $expectedResult = file_get_contents(dirname(__FILE__, 2) . '/fixtures/test_mediawiki_to_tiki_redirect_output.txt');
            $this->assertEquals($expectedResult, $obj->transform($source, 'Tiki'));
        }*/

}
