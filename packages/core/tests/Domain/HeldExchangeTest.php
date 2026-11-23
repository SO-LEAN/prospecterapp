<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Connectors\Domain\Model\Channel;
use Prospecter\Core\Connectors\Domain\Model\ExchangeId;
use Prospecter\Core\Connectors\Domain\Model\ExchangeStatus;
use Prospecter\Core\Connectors\Domain\Model\HeldExchange;
use Prospecter\Core\Connectors\Domain\Model\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Only a planned video call without a link asks the provider to create one (ADR 0137). An event put again once its
 * exchange is done, or once its contact is forgotten, asks for nothing.
 */
class HeldExchangeTest extends TestCase
{
    private const string EXCHANGE = '0190a1c0-0000-7000-8000-000000000003';
    private const string ACCOUNT = '0190a1c0-0000-7000-8000-000000000001';

    public function testAPlannedVideoCallAskingItsLinkAsksTheProvider(): void
    {
        $this->assertTrue($this->aVideoCall(ExchangeStatus::Planned)->asCalendarEvent(null, null)->createsALink);
    }

    #[DataProvider('statusesButPlanned')]
    public function testAVideoCallNoLongerPlannedAsksNothing(ExchangeStatus $status): void
    {
        $this->assertFalse($this->aVideoCall($status)->asCalendarEvent(null, null)->createsALink);
    }

    /**
     * @return iterable<string, array{ExchangeStatus}>
     */
    public static function statusesButPlanned(): iterable
    {
        yield 'done' => [ExchangeStatus::Done];
        yield 'missed' => [ExchangeStatus::Missed];
    }

    public function testAVideoCallWithItsLinkAsksNothing(): void
    {
        $this->assertFalse($this->aVideoCall(ExchangeStatus::Planned, 'https://meet.google.com/abc-defg-hij')->asCalendarEvent(null, null)->createsALink);
    }

    private function aVideoCall(ExchangeStatus $status, ?string $link = null): HeldExchange
    {
        return new HeldExchange(new ExchangeId(self::EXCHANGE), new AccountId(self::ACCOUNT), new MemberId(self::ACCOUNT), $status, Channel::VideoCall, new \DateTimeImmutable('2026-11-17 13:00 UTC'), 30, $link, null, new OrganizationId(self::ACCOUNT), 'ACME', linkRequested: true);
    }
}
