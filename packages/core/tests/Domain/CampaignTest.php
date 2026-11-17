<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Campaigns\Domain\Event\CampaignDiscarded;
use Prospecter\Core\Campaigns\Domain\Event\CampaignPrepared;
use Prospecter\Core\Campaigns\Domain\Event\CampaignRevised;
use Prospecter\Core\Campaigns\Domain\Model\Campaign;
use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\Campaigns\Domain\Model\CampaignIntent;
use Prospecter\Core\Campaigns\Domain\Model\CampaignName;
use Prospecter\Core\Campaigns\Domain\Model\CampaignStatus;
use Prospecter\Core\Campaigns\Domain\Model\Channel;
use Prospecter\Core\Campaigns\Domain\Model\MessageTemplate;
use Prospecter\Core\Campaigns\Domain\Model\Targeting;
use Prospecter\Core\Campaigns\Domain\Policy\CampaignPossibilities;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Tests\Core\Double\StateOf;

/**
 * Every intention of a campaign is offered by its state, and records its event.
 */
final class CampaignTest extends TestCase
{
    private const string ACCOUNT = '0190a1c0-0000-7000-8000-000000000001';
    private const string ME = '0190a1c0-0000-7000-8000-000000000002';
    private const string CAMPAIGN = '0190a1c0-0000-7000-8000-000000000003';
    private const string NOW = '2026-11-17 10:00';

    public function testPreparingRecordsIt(): void
    {
        $this->assertEquals([new CampaignPrepared(new AccountId(self::ACCOUNT), new MemberId(self::ME), new CampaignId(self::CAMPAIGN), $this->now())], $this->prepared()->releaseEvents());
    }

    public function testAPreparedCampaignIsADraft(): void
    {
        $this->assertSame(CampaignStatus::Draft, StateOf::the($this->prepared())['status']);
    }

    public function testADraftOffersEveryIntent(): void
    {
        $this->assertSame(CampaignIntent::cases(), CampaignPossibilities::given(CampaignStatus::Draft));
    }

    #[DataProvider('intents')]
    public function testAnIntentRecordsItsEvent(CampaignIntent $intent, string $event): void
    {
        $campaign = $this->prepared();
        $campaign->releaseEvents();

        match ($intent) {
            CampaignIntent::ReviseCampaign => $campaign->revise(CampaignName::of('Relance'), Targeting::by(Channel::Call, ['#ESN']), MessageTemplate::of('Bonjour', null, Channel::Call), new MemberId(self::ME), $this->now()),
            CampaignIntent::DiscardCampaign => $campaign->discard(new MemberId(self::ME), $this->now()),
        };

        $this->assertEquals([new $event(new AccountId(self::ACCOUNT), new MemberId(self::ME), new CampaignId(self::CAMPAIGN), $this->now())], $campaign->releaseEvents());
    }

    /**
     * @return iterable<string, array{CampaignIntent, class-string}>
     */
    public static function intents(): iterable
    {
        yield 'revise' => [CampaignIntent::ReviseCampaign, CampaignRevised::class];
        yield 'discard' => [CampaignIntent::DiscardCampaign, CampaignDiscarded::class];
    }

    public function testRevisingChangesEverythingButItsAuthor(): void
    {
        $campaign = $this->prepared();
        $targeting = Targeting::by(Channel::LinkedIn, ['#ESN', '#PHP']);
        $message = MessageTemplate::of('Salut {prénom}', null, Channel::LinkedIn);
        $campaign->revise(CampaignName::of('Relance PHP'), $targeting, $message, new MemberId('0190a1c0-0000-7000-8000-000000000009'), $this->now());
        $state = StateOf::the($campaign);

        $this->assertEquals([CampaignName::of('Relance PHP'), $targeting, $message, new MemberId(self::ME)], [$state['name'], $state['targeting'], $state['message'], $state['createdBy']]);
    }

    private function prepared(): Campaign
    {
        return Campaign::prepare(
            new CampaignId(self::CAMPAIGN),
            new AccountId(self::ACCOUNT),
            CampaignName::of('Relance ESN'),
            Targeting::by(Channel::Email, ['#ESN']),
            MessageTemplate::of('Bonjour {prénom}', 'Une question', Channel::Email),
            new MemberId(self::ME),
            $this->now(),
        );
    }

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(self::NOW);
    }
}
