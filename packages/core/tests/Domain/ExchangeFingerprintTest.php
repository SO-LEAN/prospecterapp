<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\Direction;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFingerprint;

class ExchangeFingerprintTest extends TestCase
{
    private const string ADA = '0190a0a0-0000-7000-8000-000000000001';
    private const string GRACE = '0190a0a0-0000-7000-8000-000000000002';

    public function testAnEMailWithAnotherCorrespondentIsAnotherExchange(): void
    {
        $this->assertNotEquals(ExchangeFingerprint::ofEmail('identity', new ContactId(self::ADA)), ExchangeFingerprint::ofEmail('identity', new ContactId(self::GRACE)));
    }

    public function testALinkedInMessageIsTheSameWhateverTheCaseTheSpacesAndThePunctuationOfItsText(): void
    {
        $this->assertEquals(
            ExchangeFingerprint::ofLinkedInMessage('2-abc', Direction::Inbound, new \DateTimeImmutable('2026-11-18 09:12:05 UTC'), 'Bonjour Michel !'),
            ExchangeFingerprint::ofLinkedInMessage('2-abc', Direction::Inbound, new \DateTimeImmutable('2026-11-18 09:12:05 UTC'), ' bonjour  Michel'),
        );
    }

    public function testTheSameTextAMinuteLaterIsAnotherMessage(): void
    {
        $this->assertNotEquals(
            ExchangeFingerprint::ofLinkedInMessage('2-abc', Direction::Inbound, new \DateTimeImmutable('2026-11-18 09:12:05 UTC'), 'Bonjour'),
            ExchangeFingerprint::ofLinkedInMessage('2-abc', Direction::Inbound, new \DateTimeImmutable('2026-11-18 09:13:05 UTC'), 'Bonjour'),
        );
    }

    public function testTheMinuteIsTheSameWhateverTheTimeZoneItIsReadIn(): void
    {
        $this->assertEquals(
            ExchangeFingerprint::ofLinkedInMessage('2-abc', Direction::Inbound, new \DateTimeImmutable('2026-11-18 09:12:05 UTC'), 'Bonjour'),
            ExchangeFingerprint::ofLinkedInMessage('2-abc', Direction::Inbound, new \DateTimeImmutable('2026-11-18 10:12:30 Europe/Paris'), 'Bonjour'),
        );
    }

    public function testWhatWasSaidStaysOutOfTheFingerprint(): void
    {
        $fingerprint = ExchangeFingerprint::ofLinkedInMessage('2-abc', Direction::Inbound, new \DateTimeImmutable('2026-11-18 09:12 UTC'), 'Bonjour');

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $fingerprint->value);
    }

    public function testAMessageOfAnotherChannelIsAnotherExchange(): void
    {
        $at = new \DateTimeImmutable('2026-11-18 09:12 UTC');

        $this->assertNotEquals(
            ExchangeFingerprint::ofMessage(new ExchangeFacts(Channel::Sms, Direction::Inbound, $at, 'Bonjour'), new ContactId(self::ADA)),
            ExchangeFingerprint::ofMessage(new ExchangeFacts(Channel::Malt, Direction::Inbound, $at, 'Bonjour'), new ContactId(self::ADA)),
        );
    }
}
