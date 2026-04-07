<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Test\BBCode\Validator;

use Horde\Text\Wiki\BBCode\Validator\ColorAttributeValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ColorAttributeValidator::class)]
class ColorAttributeValidatorTest extends TestCase
{
    private ColorAttributeValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ColorAttributeValidator();
    }

    #[DataProvider('validColorProvider')]
    public function testValidColors(string $color): void
    {
        $this->assertTrue($this->validator->validate($color));
    }

    #[DataProvider('invalidColorProvider')]
    public function testInvalidColors(string $color): void
    {
        $this->assertFalse($this->validator->validate($color));
    }

    public static function validColorProvider(): array
    {
        return [
            'hex 3-digit' => ['#F00'],
            'hex 6-digit' => ['#FF0000'],
            'hex lowercase' => ['#ff0000'],
            'hex mixed case' => ['#Ff00Aa'],
            'named black' => ['black'],
            'named red' => ['red'],
            'named Blue (uppercase)' => ['Blue'],
            'rgb valid' => ['rgb(255, 0, 0)'],
            'rgb with spaces' => ['rgb(  255  ,  0  ,  0  )'],
            'rgb zero' => ['rgb(0, 0, 0)'],
            'rgb max' => ['rgb(255, 255, 255)'],
        ];
    }

    public static function invalidColorProvider(): array
    {
        return [
            'empty string' => [''],
            'CSS injection semicolon' => ['red; background: url(evil)'],
            'CSS injection quote' => ['red" style="display:none'],
            'hex too short' => ['#F'],
            'hex too long' => ['#FF00000'],
            'hex invalid char' => ['#GG0000'],
            'rgb over 255' => ['rgb(256, 0, 0)'],
            'rgb negative' => ['rgb(-1, 0, 0)'],
            'rgb missing parens' => ['rgb 255, 0, 0'],
            'unknown name' => ['notacolor'],
        ];
    }
}
