<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Test\BBCode\Validator;

use Horde\Text\Wiki\BBCode\Validator\EmailAttributeValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(EmailAttributeValidator::class)]
class EmailAttributeValidatorTest extends TestCase
{
    private EmailAttributeValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new EmailAttributeValidator();
    }

    public function testNoEmailAttributeIsValid(): void
    {
        // When no 'email' attribute, validation passes (content-based form)
        $this->assertTrue($this->validator->validate([]));
    }

    #[DataProvider('validEmailProvider')]
    public function testValidEmails(string $email): void
    {
        $this->assertTrue($this->validator->validate(['email' => $email]));
    }

    #[DataProvider('invalidEmailProvider')]
    public function testInvalidEmails(string $email): void
    {
        $this->assertFalse($this->validator->validate(['email' => $email]));
    }

    public static function validEmailProvider(): array
    {
        return [
            'simple email' => ['user@example.com'],
            'subdomain' => ['user@mail.example.com'],
            'with plus' => ['user+tag@example.com'],
            'with dots' => ['first.last@example.com'],
            'with numbers' => ['user123@example456.com'],
            'with hyphen' => ['user@ex-ample.com'],
        ];
    }

    public static function invalidEmailProvider(): array
    {
        return [
            'empty string' => [''],
            'no at sign' => ['userexample.com'],
            'no domain' => ['user@'],
            'no local part' => ['@example.com'],
            'multiple at signs' => ['user@@example.com'],
            'spaces' => ['user @example.com'],
            'missing dot in domain' => ['user@example'],
        ];
    }
}
