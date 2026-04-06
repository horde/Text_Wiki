<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Bug regression tests for Text_Wiki Default parser
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class BugRegressionTest extends TestCase
{
    private TextWikiBase $wiki;

    protected function setUp(): void
    {
        $this->wiki = TextWikiBase::factory('Default');
    }

    /**
     * Test nested list parsing
     *
     * @see http://pear.php.net/bugs/bug.php?id=18289
     */
    public function testBug18289NestedLists(): void
    {
        $text = '    * level1
     * level2
    * level1
     * level2';

        $html = $this->wiki->transform($text);

        // strip all whitespace to make assertEquals() easier
        $html = preg_replace('/\s+/', '', $html);

        $expected  = '<ul><li>level1<ul><li>level2</li></ul></li>';
        $expected .= '<li>level1<ul><li>level2</li></ul></li></ul>';
        $this->assertEquals($expected, $html);
    }

    /**
     * Test that large code blocks don't cause blank page/failure
     *
     * @see http://pear.php.net/bugs/bug11649
     */
    public function testBug11649LargeCodeBlock(): void
    {
        $data = file_get_contents(__DIR__ . '/../fixtures/bug11649.txt');
        $html = $this->wiki->transform($data);

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('<code>', $html);
    }
}
