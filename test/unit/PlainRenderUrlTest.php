<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test Plain text URL rendering
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class PlainRenderUrlTest extends TestCase
{
    public function testRenderUrlToPlainText(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Url']);

        $input = '
[http://www.example.com/page An example page]
http://www.example.com/page
';

        $expected = '
An example page
http://www.example.com/page
';

        $this->assertEquals($expected, $wiki->transform($input, 'Plain'));
    }
}
