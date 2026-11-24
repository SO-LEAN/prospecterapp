<?php

declare(strict_types=1);

namespace Tests\Core\Application\Prospecting;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Connectors\Contract\Event\EmailExchanged;
use Prospecter\Core\Prospecting\Application\NoteImportedEmail\NoteImportedEmail;
use Prospecter\Core\Prospecting\Application\Policy\NoteImportedEmailPolicy;

class NoteImportedEmailPolicyTest extends TestCase
{
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000001';
    private const string MEMBER = '01960000-0000-7000-8000-000000000002';
    private const string CONTACT = '01960000-0000-7000-8000-000000000003';
    private const string EXCHANGED_AT = '2026-11-24 17:30 UTC';
    private const string AT = '2026-11-25 09:00 UTC';
    private const array ATTACHMENTS = [['upload' => 'uploads/offer.pdf', 'name' => 'offer.pdf', 'size' => 1024]];
    private const array LEFT_OUT = [['name' => 'movie.mp4', 'size' => 90_000_000]];

    public function testAnEmailExchangedWithAContactIsNotedAsAnExchange(): void
    {
        $exchanged = new EmailExchanged(
            accountId: self::ACCOUNT,
            memberId: self::MEMBER,
            provider: 'google',
            emailId: '<offer@example.com>',
            direction: 'inbound',
            contactId: self::CONTACT,
            organizationId: null,
            subject: 'Our offer',
            text: 'Please find our offer attached.',
            exchangedAt: new \DateTimeImmutable(self::EXCHANGED_AT),
            occurredAt: new \DateTimeImmutable(self::AT),
            providerMessageId: '18c2f0a1b3d4e5f6',
            attachments: self::ATTACHMENTS,
            attachmentsLeftOut: self::LEFT_OUT,
        );

        $this->assertEquals(new NoteImportedEmail(
            memberId: self::MEMBER,
            provider: 'google',
            emailId: '<offer@example.com>',
            direction: 'inbound',
            contactId: self::CONTACT,
            organizationId: null,
            subject: 'Our offer',
            text: 'Please find our offer attached.',
            exchangedAt: new \DateTimeImmutable(self::EXCHANGED_AT),
            providerMessageId: '18c2f0a1b3d4e5f6',
            attachments: self::ATTACHMENTS,
            attachmentsLeftOut: self::LEFT_OUT,
            executedAt: new \DateTimeImmutable(self::AT),
        ), new NoteImportedEmailPolicy()($exchanged));
    }
}
