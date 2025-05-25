<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

// class to test the Text_Wiki::transform() with different wiki markups
#[CoversNothing]
class DefaultParserBug18289Test extends TestCase
{
    protected $wiki;

    protected function setUp(): void
    {
        $this->wiki = TextWikiBase::factory('Default');
    }

    protected function tearDown(): void
    {
        unset($this->wiki);
    }

    /**
     * @see http://pear.php.net/bugs/bug.php?id=18289
     */
    public function test18289(): void
    {
        $text = <<<EOT
            * level1
             * level2
            * level1
             * level2
            EOT;

        $html = $this->wiki->transform($text);

        // strip all whitespace to make assertEquals() easier
        $html = preg_replace('/\s+/', '', $html);

        $assertion  = '<ul><li>level1<ul><li>level2</li></ul></li>';
        $assertion .= '<li>level1<ul><li>level2</li></ul></li></ul>';
        $this->assertEquals($assertion, $html);
    }

    /**
     * <code> parsing fails ("blank page") for large data. Let's make sure it works.
     *
     * @see  http://pear.php.net/bugs/bug11649
     */
    public function testbug11649()
    {
        $data = file_get_contents(dirname(__FILE__) . '/fixtures/bug11649.txt');
        $html = $this->wiki->transform($data);
        $this->assertTrue(is_string($html));
    }
}
