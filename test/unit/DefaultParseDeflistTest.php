<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test Default parser Deflist (definition list) parsing
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class DefaultParseDeflistTest extends TestCase
{
    public function testSimpleDeflistParsing(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Deflist']);

        $input = ": term : definition";
        $wiki->parse($input);

        $deflistTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Deflist');
        $this->assertGreaterThan(0, count($deflistTokens));
    }

    public function testMultipleDefinitions(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Deflist']);

        $input = ": term1 : definition1\n: term2 : definition2";
        $wiki->parse($input);

        $deflistTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Deflist');
        $this->assertGreaterThan(0, count($deflistTokens));
    }

    public function testDeflistToXhtmlRendering(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Deflist']);

        $input = ": API : Application Programming Interface";
        $output = $wiki->transform($input, 'Xhtml');

        $this->assertStringContainsString('<dl', $output);
        $this->assertStringContainsString('<dt', $output);
        $this->assertStringContainsString('<dd', $output);
        $this->assertStringContainsString('API', $output);
        $this->assertStringContainsString('Application Programming Interface', $output);
    }

    public function testNestedDeflist(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Deflist']);

        $input = ": term : definition\n:: nested term :: nested definition";
        $wiki->parse($input);

        $deflistTokens = array_filter($wiki->tokens, fn($t) => $t[0] === 'Deflist');
        $this->assertGreaterThan(0, count($deflistTokens));
    }
}
