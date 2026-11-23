<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\Direction;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFingerprint;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\ImportedEmail;
use Prospecter\Core\Prospecting\Domain\Model\MessageReference;
use Prospecter\Core\Prospecting\Domain\Model\Noting;

class NotingTest extends TestCase
{
    private const string ADA = '0190a0a0-0000-7000-8000-000000000001';
    private const string AT = '2026-11-18 09:12 UTC';

    public function testAnImportedEMailIsFingerprintedByItsIdentity(): void
    {
        $noting = Noting::imported(self::facts(), new ImportedEmail('gmail', 'identity-of-the-email'));

        $this->assertEquals(ExchangeFingerprint::ofEmail('identity-of-the-email', self::ada()), $noting->fingerprintWith(self::ada()));
    }

    public function testAnImportedEMailIsDone(): void
    {
        $this->assertSame(ExchangeStatus::Done, Noting::imported(self::facts(), new ImportedEmail('gmail', 'identity-of-the-email'))->outcome);
    }

    public function testAnExchangeNotedIsFingerprintedByWhereItWasRead(): void
    {
        $reference = MessageReference::of(Channel::LinkedIn, messageId: null, linkedInConversation: '2-abc');
        $noting = Noting::of(self::facts(), ExchangeStatus::Done, $reference);

        $this->assertEquals($reference->fingerprintOf(self::facts(), self::ada()), $noting->fingerprintWith(self::ada()));
    }

    public function testAnExchangeNotedWithoutReferenceIsFingerprintedByItsFacts(): void
    {
        $noting = Noting::of(self::facts(), ExchangeStatus::Missed);

        $this->assertEquals(MessageReference::none()->fingerprintOf(self::facts(), self::ada()), $noting->fingerprintWith(self::ada()));
    }

    private static function facts(): ExchangeFacts
    {
        return new ExchangeFacts(Channel::LinkedIn, Direction::Inbound, new \DateTimeImmutable(self::AT), 'Bonjour');
    }

    private static function ada(): ContactId
    {
        return new ContactId(self::ADA);
    }
}
