<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test XHTML URL rendering
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class XhtmlRenderUrlTest extends TestCase
{
    public function testRenderUrlWithCustomTargetAttribute(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Url']);
        $wiki->setRenderConf('Xhtml', 'Url', 'target', '');

        $input = '
[http://www.example.com/page An example page]
http://www.example.com/page
';

        $expected = '<a href="http://www.example.com/page">An example page</a>
<a href="http://www.example.com/page">http://www.example.com/page</a>
';

        $this->assertEquals($expected, $wiki->transform($input, 'Xhtml'));
    }
}
