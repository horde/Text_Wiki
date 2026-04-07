<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Test\BBCode\Validator;

use Horde\Text\Wiki\BBCode\Validator\UrlAttributeValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(UrlAttributeValidator::class)]
class UrlAttributeValidatorTest extends TestCase
{
    private UrlAttributeValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new UrlAttributeValidator();
    }

    #[DataProvider('validUrlProvider')]
    public function testValidUrls(string $url): void
    {
        $this->assertTrue($this->validator->validate($url));
    }

    #[DataProvider('invalidUrlProvider')]
    public function testInvalidUrls(string $url): void
    {
        $this->assertFalse($this->validator->validate($url));
    }

    public static function validUrlProvider(): array
    {
        return [
            'http URL' => ['http://example.com'],
            'https URL' => ['https://example.com/path'],
            'ftp URL' => ['ftp://ftp.example.com'],
            'ftps URL' => ['ftps://ftp.example.com'],
            'mailto' => ['mailto:user@example.com'],
            'relative path' => ['/path/to/page'],
            'relative with query' => ['/page?foo=bar'],
            'hash only' => ['#anchor'],
            'tel link' => ['tel:+1234567890'],
            'ssh URL' => ['ssh://user@host'],
            'sftp URL' => ['sftp://host/path'],
            'news URL' => ['news:comp.lang.php'],
            'nntp URL' => ['nntp://news.example.com'],
        ];
    }

    public static function invalidUrlProvider(): array
    {
        return [
            'empty string' => [''],
            'javascript XSS' => ['javascript:alert(1)'],
            'data URI XSS' => ['data:text/html,<script>alert(1)</script>'],
            'vbscript XSS' => ['vbscript:msgbox(1)'],
            'file protocol' => ['file:///etc/passwd'],
            'unknown scheme' => ['foo://bar'],
        ];
    }
}
