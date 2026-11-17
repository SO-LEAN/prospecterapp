<?php

namespace Tests\App\Campaigns;

use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\Port\CampaignSheetRetriever;
use Prospecter\Core\Campaigns\Domain\Exception\UnknownCampaign;
use Prospecter\Core\Campaigns\Domain\Model\Campaign;
use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\Campaigns\Domain\Model\CampaignName;
use Prospecter\Core\Campaigns\Domain\Model\Channel;
use Prospecter\Core\Campaigns\Domain\Model\MessageTemplate;
use Prospecter\Core\Campaigns\Domain\Model\Targeting;
use Prospecter\Core\Campaigns\Domain\Repository\CampaignRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Tests\App\Base\ApiTestCase;

/**
 * The campaigns of an account in its partition of the table of Campaigns (ADR 0144): restored as saved, read without
 * being restored, every one of the account at once, the newest first, none of another account.
 */
class DynamoCampaignRepositoryTest extends ApiTestCase
{
    private AccountId $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->account = new AccountId(self::newIdentity());
    }

    public function testACampaignIsRestoredAsSaved(): void
    {
        $campaign = $this->aCampaign('Relance ESN');
        $this->repository()->save($campaign);
        $campaign->releaseEvents();

        $this->assertEquals($campaign, $this->repository()->get($this->account, $campaign->id()));
    }

    public function testItsSheetSaysItsTargetedTagsAndWhatItsStateOffers(): void
    {
        $campaign = $this->aCampaign('Relance ESN');
        $this->repository()->save($campaign);

        $sheet = $this->retriever()->get($this->account, $campaign->id());

        $this->assertSame(
            ['Relance ESN', 'email', ['#ESN'], ['#ESN', '#EMAIL'], 'Bonjour {prénom}', 'Une question', 'draft', ['ReviseCampaign', 'DiscardCampaign']],
            [$sheet->name, $sheet->channel, $sheet->tags, $sheet->targetedTags, $sheet->message, $sheet->subject, $sheet->status, $sheet->possibleIntents],
        );
    }

    public function testTheCampaignsOfTheAccountAreReadAtOnceTheNewestFirst(): void
    {
        $older = $this->aCampaign('Relance ESN');
        $newer = $this->aCampaign('Appels ESN');
        $this->repository()->save($older);
        $this->repository()->save($newer);
        $this->repository()->save($this->aCampaign('La leur', new AccountId(self::newIdentity())));

        $this->assertSame(['Appels ESN', 'Relance ESN'], array_map(static fn (CampaignSheet $sheet): string => $sheet->name, $this->retriever()->ofAccount($this->account)));
    }

    public function testACampaignRemovedOrOfAnotherAccountIsUnknown(): void
    {
        $campaign = $this->aCampaign('Relance ESN');
        $this->repository()->save($campaign);

        try {
            $this->repository()->get(new AccountId(self::newIdentity()), $campaign->id());
            $this->fail('A campaign of another account was found');
        } catch (UnknownCampaign) {
        }

        $this->repository()->remove($campaign);
        $this->expectException(UnknownCampaign::class);

        $this->retriever()->get($this->account, $campaign->id());
    }

    private function aCampaign(string $name, ?AccountId $account = null): Campaign
    {
        return Campaign::prepare(
            new CampaignId(self::newIdentity()),
            $account ?? $this->account,
            CampaignName::of($name),
            Targeting::by(Channel::Email, ['#ESN']),
            MessageTemplate::of('Bonjour {prénom}', 'Une question', Channel::Email),
            new MemberId(self::newIdentity()),
            new \DateTimeImmutable('2026-11-17 10:00:00'),
        );
    }

    private function repository(): CampaignRepository
    {
        return self::getContainer()->get(CampaignRepository::class);
    }

    private function retriever(): CampaignSheetRetriever
    {
        return self::getContainer()->get(CampaignSheetRetriever::class);
    }
}
