<?php

declare(strict_types=1);

namespace Tests\Core\Application\Prospecting;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Application\DiscardRemovedAttachment\DiscardRemovedAttachment;
use Prospecter\Core\Prospecting\Application\Policy\DiscardRemovedAttachmentPolicy;
use Prospecter\Core\Prospecting\Domain\Event\AttachmentRemoved;
use Prospecter\Core\Prospecting\Domain\Model\AttachmentId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

class DiscardRemovedAttachmentPolicyTest extends TestCase
{
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000001';
    private const string MEMBER = '01960000-0000-7000-8000-000000000002';
    private const string EXCHANGE = '01960000-0000-7000-8000-000000000003';
    private const string ATTACHMENT = '01960000-0000-7000-8000-000000000004';
    private const string FILE = 'attachments/offer.pdf';
    private const string AT = '2026-11-24 09:00 UTC';

    public function testARemovedAttachmentsFileLeavesThePrivateStorage(): void
    {
        $removed = new AttachmentRemoved(new AccountId(self::ACCOUNT), new MemberId(self::MEMBER), new ExchangeId(self::EXCHANGE), new AttachmentId(self::ATTACHMENT), self::FILE, new \DateTimeImmutable(self::AT));

        $this->assertEquals(new DiscardRemovedAttachment(self::FILE, new \DateTimeImmutable(self::AT)), new DiscardRemovedAttachmentPolicy()($removed));
    }
}
