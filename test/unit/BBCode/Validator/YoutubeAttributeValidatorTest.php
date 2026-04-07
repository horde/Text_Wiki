<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Test\BBCode\Validator;

use Horde\Text\Wiki\BBCode\Validator\YoutubeAttributeValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(YoutubeAttributeValidator::class)]
class YoutubeAttributeValidatorTest extends TestCase
{
    private YoutubeAttributeValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new YoutubeAttributeValidator();
    }

    public function testAttributeValidationAlwaysPasses(): void
    {
        // YouTube tag uses content, not attributes
        $this->assertTrue($this->validator->validate([]));
        $this->assertTrue($this->validator->validate(['foo' => 'bar']));
    }

    #[DataProvider('validVideoIdProvider')]
    public function testValidVideoIds(string $videoId): void
    {
        $this->assertTrue($this->validator->validateVideoId($videoId));
    }

    #[DataProvider('invalidVideoIdProvider')]
    public function testInvalidVideoIds(string $videoId): void
    {
        $this->assertFalse($this->validator->validateVideoId($videoId));
    }

    public static function validVideoIdProvider(): array
    {
        return [
            'standard video' => ['dQw4w9WgXcQ'],
            'with underscore' => ['dQw4w9Wg_cQ'],
            'with hyphen' => ['dQw4w9Wg-cQ'],
            'all numbers' => ['12345678901'],
            'all letters' => ['abcdefghijk'],
        ];
    }

    public static function invalidVideoIdProvider(): array
    {
        return [
            'empty string' => [''],
            'too short' => ['dQw4w9WgXc'],
            'too long' => ['dQw4w9WgXcQQ'],
            'with spaces' => ['dQw4w9WgXc '],
            'with special chars' => ['dQw4w9WgXc!'],
            'with slash' => ['dQw4w9Wg/cQ'],
            'with dot' => ['dQw4w9Wg.cQ'],
        ];
    }
}
