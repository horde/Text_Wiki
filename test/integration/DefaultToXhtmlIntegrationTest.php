<?php

namespace Horde\Text\Wiki\Test\Integration;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Integration test: Default engine to XHTML output
 *
 * Tests full wiki document transformation with multiple parsers
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class DefaultToXhtmlIntegrationTest extends TestCase
{
    private TextWikiBase $wiki;

    protected function setUp(): void
    {
        $this->wiki = TextWikiBase::factory('Default');
    }

    public function testSimpleDocumentTransformation(): void
    {
        $input = <<<WIKI
+ Main Heading

This is a paragraph with '''bold''' and ''italic'' text.

++ Subheading

* List item 1
* List item 2
* List item 3
WIKI;

        $output = $this->wiki->transform($input, 'Xhtml');

        // Check heading
        $this->assertStringContainsString('<h1', $output);
        $this->assertStringContainsString('Main Heading', $output);

        // Check formatting
        $this->assertMatchesRegularExpression('/<(strong|b)>bold<\/(strong|b)>/', $output);
        $this->assertMatchesRegularExpression('/<(em|i)>italic<\/(em|i)>/', $output);

        // Check subheading
        $this->assertStringContainsString('<h2', $output);
        $this->assertStringContainsString('Subheading', $output);

        // Check list
        $this->assertStringContainsString('<ul', $output);
        $this->assertStringContainsString('<li>', $output);
        $this->assertStringContainsString('List item 1', $output);
    }

    public function testComplexDocumentWithAllFeatures(): void
    {
        $input = <<<WIKI
+ Wiki Formatting Test

This is a WikiWord link and a ((Free Link)) example.

++ Text Formatting

'''Bold text''', ''italic text'', and '''''bold italic'''''.

Also __underlined__ and ^^superscript^^ and ,,subscript,,.

++ Lists and Structure

* Unordered item 1
** Nested item
* Unordered item 2

# Ordered item 1
# Ordered item 2

+++ Blockquotes

> This is a quoted text.
> It continues here.

++++ Tables

|| Header 1 || Header 2 ||
|| Cell 1   || Cell 2   ||
|| Cell 3   || Cell 4   ||

+++++ Links and Media

Visit http://example.com for info.

[http://example.com Link Text]

[[image.jpg]]

++++++ Horizontal Rule

----

= Centered text =

<code>
function test() {
    return true;
}
</code>
WIKI;

        $output = $this->wiki->transform($input, 'Xhtml');

        // Verify multiple features are present
        $this->assertStringContainsString('<h1', $output);
        $this->assertStringContainsString('<h2', $output);
        $this->assertStringContainsString('<h3', $output);
        $this->assertStringContainsString('<h4', $output);
        $this->assertStringContainsString('<h5', $output);
        $this->assertStringContainsString('<h6', $output);

        $this->assertMatchesRegularExpression('/<(strong|b)>/', $output);
        $this->assertMatchesRegularExpression('/<(em|i)>/', $output);

        $this->assertStringContainsString('<ul', $output);
        $this->assertStringContainsString('<ol', $output);

        $this->assertStringContainsString('<blockquote', $output);

        $this->assertStringContainsString('<table', $output);
        $this->assertStringContainsString('Header 1', $output);

        $this->assertStringContainsString('<a', $output);
        $this->assertStringContainsString('http://example.com', $output);

        $this->assertStringContainsString('<img', $output);

        $this->assertStringContainsString('<hr', $output);

        $this->assertStringContainsString('<code>', $output);
        $this->assertStringContainsString('function test', $output);
    }

    public function testNestedStructures(): void
    {
        $input = <<<WIKI
+ Document with Nesting

* First level
** Second level
*** Third level
*** Another third
** Back to second
* Back to first

# Ordered first
## Ordered second
### Ordered third
## Back to second
# Back to first
WIKI;

        $output = $this->wiki->transform($input, 'Xhtml');

        $this->assertStringContainsString('<ul', $output);
        $this->assertStringContainsString('<ol', $output);

        // Check nesting exists
        $this->assertGreaterThan(1, substr_count($output, '<ul'));
        $this->assertGreaterThan(1, substr_count($output, '<ol'));
    }

    public function testLinksAndReferences(): void
    {
        $input = <<<WIKI
+ Links Test

WikiWord automatic link.

((Free Link)) with parens.

((Page|Display Text)) with custom text.

((Page#Anchor)) with anchor.

[http://example.com External link]

http://example.com inline URL.

[# section1]
Jump to [#section1].
WIKI;

        $output = $this->wiki->transform($input, 'Xhtml');

        $this->assertStringContainsString('<a', $output);
        $this->assertStringContainsString('WikiWord', $output);
        $this->assertStringContainsString('Free Link', $output);
        $this->assertStringContainsString('Display Text', $output);
        $this->assertStringContainsString('http://example.com', $output);
        $this->assertStringContainsString('id=', $output);
    }

    public function testCodeAndPreformatted(): void
    {
        $input = <<<WIKI
+ Code Examples

Inline <code>code here</code> works.

Block code:
<code>
function example() {
    return "test";
}
</code>

End of document.
WIKI;

        $output = $this->wiki->transform($input, 'Xhtml');

        $this->assertStringContainsString('<code>', $output);
        $this->assertStringContainsString('function example', $output);
        $this->assertStringContainsString('return', $output);
    }

    public function testMixedFormatting(): void
    {
        $input = <<<WIKI
This paragraph has '''bold ''italic'' inside''' and ''italic '''bold''' inside''.

Also '''bold with ((free link)) inside''' works.

And ((Free Link with '''bold''' inside)) too.
WIKI;

        $output = $this->wiki->transform($input, 'Xhtml');

        $this->assertMatchesRegularExpression('/<(strong|b)>/', $output);
        $this->assertMatchesRegularExpression('/<(em|i)>/', $output);
        $this->assertStringContainsString('<a', $output);
    }

    public function testSpecialCharacters(): void
    {
        $input = <<<WIKI
+ Special Characters

Text with "quotes" and 'apostrophes'.

Text with & ampersand and < less than and > greater than.

Email: test@example.com

Math: 2 + 2 = 4

Code with <special> characters.
WIKI;

        $output = $this->wiki->transform($input, 'Xhtml');

        // Should properly encode special characters
        $this->assertIsString($output);
        $this->assertNotEmpty($output);
    }

    public function testEmptyAndEdgeCases(): void
    {
        // Empty document
        $output1 = $this->wiki->transform('', 'Xhtml');
        $this->assertIsString($output1);

        // Only whitespace
        $output2 = $this->wiki->transform("   \n\n   ", 'Xhtml');
        $this->assertIsString($output2);

        // Single word
        $output3 = $this->wiki->transform('Hello', 'Xhtml');
        $this->assertStringContainsString('Hello', $output3);

        // Only formatting
        $output4 = $this->wiki->transform("'''bold'''", 'Xhtml');
        $this->assertMatchesRegularExpression('/<(strong|b)>bold<\/(strong|b)>/', $output4);
    }

    public function testLargeDocument(): void
    {
        $input = '';

        // Generate a large document
        for ($i = 1; $i <= 100; $i++) {
            $input .= "+ Heading $i\n\n";
            $input .= "This is paragraph $i with '''bold''' and ''italic'' text.\n\n";
            $input .= "* List item $i.1\n";
            $input .= "* List item $i.2\n\n";
        }

        $output = $this->wiki->transform($input, 'Xhtml');

        // Should handle large documents
        $this->assertIsString($output);
        $this->assertGreaterThan(1000, strlen($output));
        $this->assertStringContainsString('Heading 1', $output);
        $this->assertStringContainsString('Heading 100', $output);
    }

    public function testAllHeadingLevels(): void
    {
        $input = <<<WIKI
+ Level 1
++ Level 2
+++ Level 3
++++ Level 4
+++++ Level 5
++++++ Level 6
WIKI;

        $output = $this->wiki->transform($input, 'Xhtml');

        $this->assertStringContainsString('<h1', $output);
        $this->assertStringContainsString('<h2', $output);
        $this->assertStringContainsString('<h3', $output);
        $this->assertStringContainsString('<h4', $output);
        $this->assertStringContainsString('<h5', $output);
        $this->assertStringContainsString('<h6', $output);
    }
}
