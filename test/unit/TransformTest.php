<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test cross-format wiki transformations
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class TransformTest extends TestCase
{
    public function testTransformFromMediawikiToTiki(): void
    {
        $wiki = TextWikiBase::factory('Mediawiki');
        $wiki->parseConf['Wikilink']['spaceUnderscore'] = false;

        $source = file_get_contents(__DIR__ . '/../fixtures/test_mediawiki_to_tiki_source.txt');
        $expected = file_get_contents(__DIR__ . '/../fixtures/test_mediawiki_to_tiki_output.txt');

        $this->assertEquals($expected, $wiki->transform($source, 'Tiki'));
    }

    public function testTransformFromMediawikiToTikiListSyntax(): void
    {
        $wiki = TextWikiBase::factory('Mediawiki');
        $wiki->parseConf['Wikilink']['spaceUnderscore'] = false;

        $source = file_get_contents(__DIR__ . '/../fixtures/test_mediawiki_to_tiki_lists_source.txt');
        $expected = file_get_contents(__DIR__ . '/../fixtures/test_mediawiki_to_tiki_lists_output.txt');

        $this->assertEquals($expected, $wiki->transform($source, 'Tiki'));
    }

    public function testTransformFromMediawikiToTikiRedirectSyntax(): void
    {
        $wiki = TextWikiBase::factory('Mediawiki');
        $wiki->parseConf['Wikilink']['spaceUnderscore'] = false;

        $source = file_get_contents(__DIR__ . '/../fixtures/test_mediawiki_to_tiki_redirect_source.txt');
        $expected = file_get_contents(__DIR__ . '/../fixtures/test_mediawiki_to_tiki_redirect_output.txt');

        $this->assertEquals($expected, $wiki->transform($source, 'Tiki'));
    }
}
