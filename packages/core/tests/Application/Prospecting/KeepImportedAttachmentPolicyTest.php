<?php

declare(strict_types=1);

namespace Tests\Core\Application\Prospecting;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Application\Attachment\KeepImportedAttachment\KeepImportedAttachment;
use Prospecter\Core\Prospecting\Application\Policy\KeepImportedAttachmentPolicy;
use Prospecter\Core\Prospecting\Domain\Event\AttachmentImported;
use Prospecter\Core\Prospecting\Domain\Model\AttachmentId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

class KeepImportedAttachmentPolicyTest extends TestCase
{
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000001';
    private const string MEMBER = '01960000-0000-7000-8000-000000000002';
    private const string EXCHANGE = '01960000-0000-7000-8000-000000000003';
    private const string ATTACHMENT = '01960000-0000-7000-8000-000000000004';
    private const string UPLOAD = 'uploads/offer.pdf';
    private const string AT = '2026-11-24 09:00 UTC';

    public function testAnImportedAttachmentsUploadIsKept(): void
    {
        $imported = new AttachmentImported(new AccountId(self::ACCOUNT), new MemberId(self::MEMBER), new ExchangeId(self::EXCHANGE), new AttachmentId(self::ATTACHMENT), self::UPLOAD, new \DateTimeImmutable(self::AT));

        $this->assertEquals(new KeepImportedAttachment(self::UPLOAD, new \DateTimeImmutable(self::AT)), new KeepImportedAttachmentPolicy()($imported));
    }
}
