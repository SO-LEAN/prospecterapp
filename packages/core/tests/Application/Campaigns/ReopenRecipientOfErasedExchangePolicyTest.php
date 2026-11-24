<?php

declare(strict_types=1);

namespace Tests\Core\Application\Campaigns;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Campaigns\Application\Policy\ReopenRecipientOfErasedExchangePolicy;
use Prospecter\Core\Campaigns\Application\Recipient\ReopenRecipientOfErasedExchange\ReopenRecipientOfErasedExchange;
use Prospecter\Core\Prospecting\Contract\Event\ExchangeFactsChanged;

class ReopenRecipientOfErasedExchangePolicyTest extends TestCase
{
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000001';
    private const string EXCHANGE = '01960000-0000-7000-8000-000000000002';
    private const string CONTACT = '01960000-0000-7000-8000-000000000003';
    private const string CAMPAIGN = '01960000-0000-7000-8000-000000000004';
    private const string AT = '2026-11-23 09:00 UTC';

    public function testTheRecipientOfAnExchangeAMemberErasedIsReopened(): void
    {
        $this->assertEquals(self::reopening(erasedWithItsOrganization: false), new ReopenRecipientOfErasedExchangePolicy()(self::fact(present: false)));
    }

    public function testAnExchangeGoneWithItsOrganizationSaysSo(): void
    {
        $this->assertEquals(self::reopening(erasedWithItsOrganization: true), new ReopenRecipientOfErasedExchangePolicy()(self::fact(present: false, erasedWithItsOrganization: true)));
    }

    public function testAnExchangeStillPresentReopensNobody(): void
    {
        $this->assertNull(new ReopenRecipientOfErasedExchangePolicy()(self::fact(present: true)));
    }

    public function testAnExchangeOutsideAnyCampaignReopensNobody(): void
    {
        $this->assertNull(new ReopenRecipientOfErasedExchangePolicy()(self::fact(present: false, campaign: null)));
    }

    private static function fact(bool $present, ?string $campaign = self::CAMPAIGN, bool $erasedWithItsOrganization = false): ExchangeFactsChanged
    {
        return new ExchangeFactsChanged(self::ACCOUNT, self::EXCHANGE, 0, $present, null, null, new \DateTimeImmutable(self::AT), self::CONTACT, $campaign, $erasedWithItsOrganization);
    }

    private static function reopening(bool $erasedWithItsOrganization): ReopenRecipientOfErasedExchange
    {
        return new ReopenRecipientOfErasedExchange(
            accountId: self::ACCOUNT,
            campaignId: self::CAMPAIGN,
            exchangeId: self::EXCHANGE,
            erasedWithItsOrganization: $erasedWithItsOrganization,
            executedAt: new \DateTimeImmutable(self::AT),
        );
    }
}
