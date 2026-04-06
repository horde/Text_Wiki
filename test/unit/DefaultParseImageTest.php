<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test Default parser Image parsing
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class DefaultParseImageTest extends TestCase
{
    public function testSimpleImageParsing(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Image']);

        $input = '[[image image.jpg]]';
        $wiki->parse($input);

        $this->assertGreaterThan(0, count($wiki->tokens));

        $imageTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Image');
        $this->assertCount(1, $imageTokens);
    }

    public function testImageWithAltText(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Image']);

        $input = '[[image image.jpg alt="Description"]]';
        $wiki->parse($input);

        $imageTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Image');
        $this->assertGreaterThanOrEqual(1, count($imageTokens));
    }

    public function testImageWithLink(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Image']);

        $input = '[[image image.jpg link="http://example.com"]]';
        $wiki->parse($input);

        $imageTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Image');
        $this->assertGreaterThanOrEqual(1, count($imageTokens));
    }

    public function testImageToXhtmlRendering(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Image']);

        $input = '[[image test.jpg]]';
        $output = $wiki->transform($input, 'Xhtml');

        $this->assertStringContainsString('<img', $output);
        $this->assertStringContainsString('test.jpg', $output);
    }

    public function testMultipleImages(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Image']);

        $input = '[[image first.jpg]] and [[image second.png]]';
        $wiki->parse($input);

        $imageTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Image');
        $this->assertCount(2, $imageTokens);
    }
}
