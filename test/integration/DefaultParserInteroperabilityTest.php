<?php

namespace Horde\Text\Wiki\Test\Integration;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Integration test: Default parser interoperability
 *
 * Tests that multiple parsers work correctly together
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class DefaultParserInteroperabilityTest extends TestCase
{
    private TextWikiBase $wiki;

    protected function setUp(): void
    {
        $this->wiki = TextWikiBase::factory('Default');
    }

    public function testFormattingInsideLinks(): void
    {
        $input = "((Free Link with '''bold''' inside))";
        $output = $this->wiki->transform($input, 'Xhtml');

        $this->assertStringContainsString('<a', $output);
        $this->assertStringContainsString('bold', $output);
    }

    public function testLinksInsideFormatting(): void
    {
        $input = "'''Bold with ((free link)) inside'''";
        $output = $this->wiki->transform($input, 'Xhtml');

        $this->assertMatchesRegularExpression('/<(strong|b)>/', $output);
        $this->assertStringContainsString('<a', $output);
    }

    public function testFormattingInsideLists(): void
    {
        $input = <<<WIKI
* Item with '''bold'''
* Item with ''italic''
* Item with ((Free Link))
WIKI;

        $output = $this->wiki->transform($input, 'Xhtml');

        $this->assertStringContainsString('<ul', $output);
        $this->assertMatchesRegularExpression('/<(strong|b)>bold/', $output);
        $this->assertMatchesRegularExpression('/<(em|i)>italic/', $output);
        $this->assertStringContainsString('<a', $output);
    }

    public function testListsInsideBlockquotes(): void
    {
        $input = <<<WIKI
> Quoted text
> * Item 1
> * Item 2
WIKI;

        $output = $this->wiki->transform($input, 'Xhtml');

        $this->assertStringContainsString('<blockquote', $output);
        $this->assertStringContainsString('<ul', $output);
    }

    public function testTableWithFormatting(): void
    {
        $input = <<<WIKI
|| '''Header''' || ''Column'' ||
|| ((Link)) || Normal ||
WIKI;

        $output = $this->wiki->transform($input, 'Xhtml');

        $this->assertStringContainsString('<table', $output);
        $this->assertMatchesRegularExpression('/<(strong|b)>Header/', $output);
        $this->assertMatchesRegularExpression('/<(em|i)>Column/', $output);
        $this->assertStringContainsString('<a', $output);
    }

    public function testHeadingWithFormatting(): void
    {
        $input = "+ Heading with '''bold''' and ''italic''";
        $output = $this->wiki->transform($input, 'Xhtml');

        $this->assertStringContainsString('<h1', $output);
        $this->assertStringContainsString('bold', $output);
        $this->assertStringContainsString('italic', $output);
    }

    public function testCodePreservesMarkup(): void
    {
        $input = "<code>'''not bold''' and ((not link))</code>";
        $output = $this->wiki->transform($input, 'Xhtml');

        $this->assertStringContainsString('<code>', $output);
        $this->assertStringContainsString("'''not bold'''", $output);
        $this->assertStringContainsString("((not link))", $output);

        // Should NOT have parsed the markup inside code
        $this->assertStringNotContainsString('<strong>', $output);
        $this->assertStringNotContainsString('<b>', $output);
    }

    public function testNestedFormattingCombinations(): void
    {
        $input = <<<WIKI
'''Bold with ''italic'' inside'''

''Italic with '''bold''' inside''

__Underline with '''bold''' and ''italic''__
WIKI;

        $output = $this->wiki->transform($input, 'Xhtml');

        $this->assertMatchesRegularExpression('/<(strong|b)>/', $output);
        $this->assertMatchesRegularExpression('/<(em|i)>/', $output);
        $this->assertMatchesRegularExpression('/<u>|text-decoration:\s*underline/i', $output);
    }

    public function testComplexNestedLists(): void
    {
        $input = <<<WIKI
* Level 1
** Level 2 with '''bold'''
*** Level 3 with ((link))
** Back to level 2
* Back to level 1

# Ordered 1
## Ordered 2 with ''italic''
# Back to 1
WIKI;

        $output = $this->wiki->transform($input, 'Xhtml');

        $this->assertStringContainsString('<ul', $output);
        $this->assertStringContainsString('<ol', $output);
        $this->assertMatchesRegularExpression('/<(strong|b)>bold/', $output);
        $this->assertMatchesRegularExpression('/<(em|i)>italic/', $output);
        $this->assertStringContainsString('<a', $output);
    }

    public function testAllFeaturesDocument(): void
    {
        $input = <<<WIKI
+ Document Title

This is a paragraph with WikiWord, ((Free Link)), '''bold''', ''italic'', __underline__, ^^super^^, and ,,sub,,.

++ Features List

* '''Bold''' list item
* ''Italic'' list item
* List with ((link))
** Nested with WikiWord
** Nested with [http://example.com URL]

+++ Table

|| '''Name''' || '''Type''' ||
|| ((Link1)) || Free ||
|| WikiWord || Auto ||

++++ Quote

> Quoted text with '''formatting'''
> And ((links))

+++++ Code

<code>
function test() {
    return '''not bold''';
}
</code>

++++++ End

----

= Centered Text =

Visit [http://example.com] or http://example.com directly.

[# anchor]
WIKI;

        $output = $this->wiki->transform($input, 'Xhtml');

        // Verify all major features rendered
        $this->assertStringContainsString('<h1', $output);
        $this->assertStringContainsString('<h2', $output);
        $this->assertStringContainsString('<h3', $output);
        $this->assertStringContainsString('<h4', $output);
        $this->assertStringContainsString('<h5', $output);
        $this->assertStringContainsString('<h6', $output);

        $this->assertStringContainsString('<ul', $output);
        $this->assertStringContainsString('<ol', $output);
        $this->assertStringContainsString('<table', $output);
        $this->assertStringContainsString('<blockquote', $output);
        $this->assertStringContainsString('<code>', $output);
        $this->assertStringContainsString('<hr', $output);

        $this->assertStringContainsString('<a', $output);
        $this->assertMatchesRegularExpression('/<(strong|b)>/', $output);
        $this->assertMatchesRegularExpression('/<(em|i)>/', $output);

        $this->assertStringContainsString('http://example.com', $output);
        $this->assertStringContainsString('id=', $output);
    }
}
