<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Test\BBCode\Validator;

use Horde\Text\Wiki\BBCode\Validator\FontAttributeValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(FontAttributeValidator::class)]
class FontAttributeValidatorTest extends TestCase
{
    private FontAttributeValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new FontAttributeValidator();
    }

    #[DataProvider('validFontProvider')]
    public function testValidFonts(string $font): void
    {
        $this->assertTrue($this->validator->validate($font));
    }

    #[DataProvider('invalidFontProvider')]
    public function testInvalidFonts(string $font): void
    {
        $this->assertFalse($this->validator->validate($font));
    }

    public static function validFontProvider(): array
    {
        return [
            'simple name' => ['Arial'],
            'with spaces' => ['Times New Roman'],
            'with hyphens' => ['Courier-New'],
            'multiple fonts' => ['Arial, Helvetica, sans-serif'],
            'with apostrophe' => ["'Courier New'"],
            'mixed case' => ['ArIaL'],
        ];
    }

    public static function invalidFontProvider(): array
    {
        return [
            'empty string' => [''],
            'CSS injection semicolon' => ['Arial; background: url(evil)'],
            'newline injection' => ["Arial\nbackground: red"],
            'control chars' => ["Arial\x00test"],
            'special chars' => ['Arial@#$%'],
        ];
    }
}
