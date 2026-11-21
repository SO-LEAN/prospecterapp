<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Domain\Model\AttachmentId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;

class ImportedAttachmentIdTest extends TestCase
{
    private const string EXCHANGE = '0190a1c0-0000-7000-8000-000000000001';
    private const string UPLOAD = '0000000000000000000000000000000a';

    public function testTheAttachmentOfAnUploadImportedTwiceIsTheSame(): void
    {
        $this->assertTrue(self::of(self::EXCHANGE, self::UPLOAD)->equals(self::of(self::EXCHANGE, self::UPLOAD)));
    }

    public function testTheAttachmentOfAnotherUploadIsAnother(): void
    {
        $this->assertFalse(self::of(self::EXCHANGE, self::UPLOAD)->equals(self::of(self::EXCHANGE, '0000000000000000000000000000000b')));
    }

    public function testTheAttachmentOfTheSameUploadInAnotherExchangeIsAnother(): void
    {
        $this->assertFalse(self::of(self::EXCHANGE, self::UPLOAD)->equals(self::of('0190a1c0-0000-7000-8000-000000000002', self::UPLOAD)));
    }

    private static function of(string $exchange, string $upload): AttachmentId
    {
        return AttachmentId::ofImportedEmail(new ExchangeId($exchange), $upload);
    }
}
