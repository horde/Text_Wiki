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

        // Default behavior: show URL in parentheses for described links
        // This preserves URL information in plain text output (useful for email, PDFs, etc.)
        $expected = '
An example page (http://www.example.com/page)
http://www.example.com/page
';

        $this->assertEquals($expected, $wiki->transform($input, 'Plain'));
    }

    public function testRenderUrlWithoutShowingUrl(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Url']);

        // Configure to hide URLs (PEAR Text_Wiki behavior)
        $wiki->setRenderConf('Plain', 'Url', ['show_url' => false]);

        $input = '
[http://www.example.com/page An example page]
http://www.example.com/page
';

        // With show_url=false, only show link text
        $expected = '
An example page
http://www.example.com/page
';

        $this->assertEquals($expected, $wiki->transform($input, 'Plain'));
    }
}
