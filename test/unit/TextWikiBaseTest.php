<?php

/**
 * Test the root TextWikiBase class
 *
 * Copyright 2025-2026 Horde LLC (http://www.horde.org/)
 *
 * @subpackage UnitTests
 * @author     Ralf Lang <ralf.lang@ralf-lang.de>
 */

namespace Horde\Text\Wiki\Test\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Horde\Text\Wiki\TextWikiBase;
use Horde\Text\Wiki\TextWikiException;
use Horde\Text\Wiki\GenericTextWikiException;
use DomainException;

#[CoversNothing]
class TextWikiBaseTest extends TestCase
{
    protected $obj;
    protected $sourceText;
    protected $tokens;
    protected $_countRulesTokens;

    public function testBaseClassCanBeInstanciated(): void
    {
        $obj = new TextWikiBase();
        $this->assertInstanceOf(TextWikiBase::class, $obj);
    }

    protected function setUp(): void
    {
        $this->obj = TextWikiBase::factory();

        $this->obj->renderConf = [];
        $this->obj->parseConf = [];
        $this->obj->formatConf = [];
        $this->obj->rules = ['Prefilter', 'Delimiter', 'Code', 'Function', 'Html', 'Raw', 'Include'];
        $this->obj->disable = ['Html', 'Include', 'Embed'];
        $this->obj->path = ['parse' => [], 'render' => []];

        $this->sourceText = 'A very \'\'simple\'\' \'\'\'source\'\'\' text. Not sure [[how]] to [http://example.com improve] the transform() tests.' . "\n";
        $this->tokens = [
            0 => [0 => 'Heading', 1 => ['type' => 'start', 'level' => 6, 'text' => 'Level 6 heading', 'id' => 'toc0']],
            1 => [0 => 'Heading', 1 => ['type' => 'end', 'level' => 6]],
            2 => [0 => 'Heading', 1 => ['type' => 'start', 'level' => 1, 'text' => 'Level 1 heading', 'id' => 'toc1']],
            3 => [0 => 'Heading', 1 => ['type' => 'end', 'level' => 1]],
            4 => [0 => 'Heading', 1 => ['type' => 'start', 'level' => 2, 'text' => 'Level 2 heading', 'id' => 'toc2']],
            5 => [0 => 'Heading', 1 => ['type' => 'end', 'level' => 2]],
            6 => [0 => 'Break', 1 => []],
            7 => [0 => 'Break', 1 => []],
        ];
        $this->_countRulesTokens = ['Heading' => 6, 'Break' => 2];
    }


    public function testFactoryThrowsExceptionOnUnknownEngineType(): void
    {
        $this->expectException(TextWikiException::class);
        $this->expectException(DomainException::class);
        $this->expectException(GenericTextWikiException::class);
        $this->expectExceptionMessage("Class '\Horde\Text\Wiki\unknownEngine' implementing parser 'unknown' does not exist or could not be autoloaded.");
        TextWikiBase::factory('unknown');
    }

}
