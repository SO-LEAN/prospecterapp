<?php

declare(strict_types=1);

namespace Tests\Core\Application\Campaigns;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Campaigns\Application\Policy\ProcessRecipientOfCampaignExchangePolicy;
use Prospecter\Core\Campaigns\Application\ProcessRecipientOfCampaignExchange\ProcessRecipientOfCampaignExchange;
use Prospecter\Core\Prospecting\Contract\Event\ExchangeFactsChanged;

class ProcessRecipientOfCampaignExchangePolicyTest extends TestCase
{
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000001';
    private const string EXCHANGE = '01960000-0000-7000-8000-000000000002';
    private const string CONTACT = '01960000-0000-7000-8000-000000000003';
    private const string CAMPAIGN = '01960000-0000-7000-8000-000000000004';
    private const string AT = '2026-11-23 09:00 UTC';

    public function testTheRecipientOfAnExchangeDoneInACampaignIsProcessedAsDone(): void
    {
        $this->assertEquals(self::processing('done'), new ProcessRecipientOfCampaignExchangePolicy()(self::fact(status: 'done')));
    }

    public function testTheRecipientOfAnExchangeMissedInACampaignIsProcessedAsMissed(): void
    {
        $this->assertEquals(self::processing('missed'), new ProcessRecipientOfCampaignExchangePolicy()(self::fact(status: 'missed')));
    }

    public function testAPlannedExchangeProcessesNobodyYet(): void
    {
        $this->assertNull(new ProcessRecipientOfCampaignExchangePolicy()(self::fact(status: 'planned')));
    }

    public function testAnExchangeOutsideAnyCampaignProcessesNobody(): void
    {
        $this->assertNull(new ProcessRecipientOfCampaignExchangePolicy()(self::fact(status: 'done', campaign: null)));
    }

    public function testAnExchangeWithNoContactProcessesNobody(): void
    {
        $this->assertNull(new ProcessRecipientOfCampaignExchangePolicy()(self::fact(status: 'done', contact: null)));
    }

    public function testAnErasedExchangeProcessesNobody(): void
    {
        $this->assertNull(new ProcessRecipientOfCampaignExchangePolicy()(self::fact(status: 'done', present: false)));
    }

    private static function fact(?string $status, bool $present = true, ?string $contact = self::CONTACT, ?string $campaign = self::CAMPAIGN): ExchangeFactsChanged
    {
        return new ExchangeFactsChanged(self::ACCOUNT, self::EXCHANGE, 2, $present, $status, new \DateTimeImmutable(self::AT), new \DateTimeImmutable(self::AT), $contact, $campaign);
    }

    private static function processing(string $outcome): ProcessRecipientOfCampaignExchange
    {
        return new ProcessRecipientOfCampaignExchange(
            accountId: self::ACCOUNT,
            campaignId: self::CAMPAIGN,
            contactId: self::CONTACT,
            exchangeId: self::EXCHANGE,
            outcome: $outcome,
            executedAt: new \DateTimeImmutable(self::AT),
        );
    }
}
