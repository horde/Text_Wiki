<?php

namespace Horde\Text\Wiki\Test\Integration;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Integration test: Default engine to Plain text output
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class DefaultToPlainIntegrationTest extends TestCase
{
    private TextWikiBase $wiki;

    protected function setUp(): void
    {
        $this->wiki = TextWikiBase::factory('Default');
    }

    public function testSimpleDocumentToPlain(): void
    {
        $input = <<<WIKI
            + Main Heading

            This is a paragraph with '''bold''' and ''italic'' text.

            * List item 1
            * List item 2
            WIKI;

        $output = $this->wiki->transform($input, 'Plain');

        $this->assertIsString($output);
        $this->assertStringContainsString('Main Heading', $output);
        $this->assertStringContainsString('bold', $output);
        $this->assertStringContainsString('italic', $output);
        $this->assertStringContainsString('List item 1', $output);
    }

    public function testPlainTextStripsFormatting(): void
    {
        $input = "'''bold''' and ''italic'' and __underline__";
        $output = $this->wiki->transform($input, 'Plain');

        // Plain text should contain the words but not the markup
        $this->assertStringContainsString('bold', $output);
        $this->assertStringContainsString('italic', $output);
        $this->assertStringContainsString('underline', $output);

        // Should NOT contain wiki markup
        $this->assertStringNotContainsString("'''", $output);
        $this->assertStringNotContainsString("''", $output);
        $this->assertStringNotContainsString('__', $output);
    }

    public function testLinksInPlainText(): void
    {
        $input = <<<WIKI
            Visit WikiWord page.

            See ((Free Link)) here.

            URL: [http://example.com Link Text]
            WIKI;

        $output = $this->wiki->transform($input, 'Plain');

        // Plain text should contain link text but not URLs
        $this->assertStringContainsString('WikiWord', $output);
        $this->assertStringContainsString('Free Link', $output);
        $this->assertStringContainsString('Link Text', $output);
    }
}
