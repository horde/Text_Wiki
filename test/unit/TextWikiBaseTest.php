<?php

/**
 * Test the root TextWikiBase class
 *
 * Copyright 2025-2025 Horde LLC (http://www.horde.org/)
 *
 * @subpackage UnitTests
 * @author     Ralf Lang <ralf.lang@ralf-lang.de>
 */

namespace Horde\Text\Wiki\Test\Unit;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Horde\Text\Wiki\TextWikiBase;

#[CoversNothing]
class TextWikiBaseTest extends TestCase
{
    public function testBaseClassCanBeInstanciated(): void
    {
        $obj = new TextWikiBase();
        $this->assertInstanceOf(TextWikiBase::class, $obj);
    }
}
