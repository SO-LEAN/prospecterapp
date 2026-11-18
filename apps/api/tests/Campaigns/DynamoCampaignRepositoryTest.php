<?php

namespace Tests\App\Campaigns;

use App\Shared\Persistence\Table;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\Port\CampaignSheetRetriever;
use Prospecter\Core\Campaigns\Application\RecipientInSheet;
use Prospecter\Core\Campaigns\Domain\Exception\UnknownCampaign;
use Prospecter\Core\Campaigns\Domain\Model\Campaign;
use Prospecter\Core\Campaigns\Domain\Model\CampaignName;
use Prospecter\Core\Campaigns\Domain\Model\Channel;
use Prospecter\Core\Campaigns\Domain\Model\ContactTags;
use Prospecter\Core\Campaigns\Domain\Model\MessageTemplate;
use Prospecter\Core\Campaigns\Domain\Model\Reach;
use Prospecter\Core\Campaigns\Domain\Model\Tag;
use Prospecter\Core\Campaigns\Domain\Model\Targeting;
use Prospecter\Core\Campaigns\Domain\Repository\CampaignRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\App\Base\ApiTestCase;

/**
 * The campaigns of an account in its partition of the table of Campaigns (ADR 0144): restored as saved, read without
 * being restored, every one of the account at once, the newest first, none of another account.
 */
class DynamoCampaignRepositoryTest extends ApiTestCase
{
    private const string ADA = '0190a1c0-0000-7000-8000-000000000a0a';
    private const string ALAN = '0190a1c0-0000-7000-8000-000000000a1a';
    private const string EXCHANGE = '0190a1c0-0000-7000-8000-000000000e0e';

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
            ['Relance ESN', 'email', ['#ESN'], ['#ESN', '#EMAIL'], 'Bonjour {prénom}', 'Une question', 'draft', ['ReviseCampaign', 'DiscardCampaign', 'FreezeCampaign']],
            [$sheet->name, $sheet->channel, $sheet->tags, $sheet->targetedTags, $sheet->message, $sheet->subject, $sheet->status, $sheet->possibleIntents],
        );
    }

    public function testAFrozenCampaignIsRestoredWithItsRecipientsInTheirOrder(): void
    {
        $campaign = $this->aFrozenCampaign(self::ADA, self::ALAN);
        $this->repository()->save($campaign);
        $campaign->releaseEvents();

        $this->assertEquals($campaign, $this->repository()->get($this->account, $campaign->id()));
        $this->assertSame([self::ADA => 'pending', self::ALAN => 'pending'], self::statusesOf($this->retriever()->get($this->account, $campaign->id())));
    }

    public function testAStartedThenStoppedCampaignIsRestoredWithItsMomentsAndItsErasedRecipients(): void
    {
        $campaign = $this->aFrozenCampaign(self::ADA, self::ALAN);
        $campaign->start(new MemberId(self::newIdentity()), new \DateTimeImmutable('2026-11-17 12:00:00'));
        $campaign->forget(new PersonId(self::ALAN), new \DateTimeImmutable('2026-11-17 12:30:00'));
        $campaign->stop(new MemberId(self::newIdentity()), new \DateTimeImmutable('2026-11-17 13:00:00'));
        $this->repository()->save($campaign);
        $campaign->releaseEvents();

        $this->assertEquals($campaign, $this->repository()->get($this->account, $campaign->id()));
        $sheet = $this->retriever()->get($this->account, $campaign->id());
        $this->assertEquals(
            ['stopped', [self::ADA => 'pending', self::ALAN => 'erased'], new \DateTimeImmutable('2026-11-17 12:00:00'), new \DateTimeImmutable('2026-11-17 13:00:00'), []],
            [$sheet->status, self::statusesOf($sheet), $sheet->startedAt, $sheet->stoppedAt, $sheet->possibleIntents],
        );
    }

    public function testACampaignLedToItsEndIsRestoredWithItsRecipientsHandledTheirExchangesAndItsProgress(): void
    {
        $campaign = $this->aFrozenCampaign(self::ADA, self::ALAN);
        $campaign->start(new MemberId(self::newIdentity()), new \DateTimeImmutable('2026-11-17 12:00:00'));
        $campaign->process(new PersonId(self::ADA), self::EXCHANGE, missed: true, at: new \DateTimeImmutable('2026-11-17 12:30:00'));
        $campaign->dismiss(new PersonId(self::ALAN), 'Parti', new MemberId(self::newIdentity()), new \DateTimeImmutable('2026-11-17 13:00:00'));
        $this->repository()->save($campaign);
        $campaign->releaseEvents();

        $this->assertEquals($campaign, $this->repository()->get($this->account, $campaign->id()));
        $sheet = $this->retriever()->get($this->account, $campaign->id());
        $this->assertEquals(
            ['ended', [self::ADA => new RecipientInSheet('processed', false, true, null, self::EXCHANGE), self::ALAN => new RecipientInSheet('dismissed', false, false, 'Parti')], 1, 1, new \DateTimeImmutable('2026-11-17 13:00:00'), false],
            [$sheet->status, $sheet->recipients, $sheet->processed, $sheet->dismissed, $sheet->endedAt, $sheet->running],
        );
    }

    public function testARecipientErasedOnceHandledIsRestoredWithWhatWasDone(): void
    {
        $campaign = $this->aFrozenCampaign(self::ADA, self::ALAN);
        $campaign->start(new MemberId(self::newIdentity()), new \DateTimeImmutable('2026-11-17 12:00:00'));
        $campaign->process(new PersonId(self::ADA), self::EXCHANGE, missed: true, at: new \DateTimeImmutable('2026-11-17 12:30:00'));
        $campaign->forget(new PersonId(self::ADA), new \DateTimeImmutable('2026-11-17 13:00:00'));
        $this->repository()->save($campaign);
        $campaign->releaseEvents();

        $this->assertEquals($campaign, $this->repository()->get($this->account, $campaign->id()));
        $sheet = $this->retriever()->get($this->account, $campaign->id());
        $this->assertEquals(
            [new RecipientInSheet('processed', false, true, null, erased: true), 1],
            [$sheet->recipients[self::ADA], $sheet->processed],
        );
    }

    public function testARecipientErasedBeforeKeepingWhatWasDoneIsReadErased(): void
    {
        $id = self::newIdentity();
        $this->campaignsTable()->batch([['PK' => 'ACCOUNT#'.$this->account, 'SK' => 'CAMPAIGN#'.$id, 'campaign' => [
            'id' => $id, 'accountId' => (string) $this->account, 'createdBy' => self::newIdentity(), 'name' => 'Relance', 'channel' => 'email', 'tags' => ['ESN'],
            'message' => 'Bonjour', 'subject' => null, 'status' => 'started', 'recipients' => [['contactId' => self::ADA, 'status' => 'erased']],
            'createdAt' => '2026-10-14T09:00:00.000000Z', 'modifiedAt' => '2026-10-14T09:00:00.000000Z', 'startedAt' => '2026-10-15T09:00:00.000000Z',
        ]]]);

        $this->assertEquals(new RecipientInSheet('erased', false, false, null, erased: true), $this->retriever()->get($this->account, new CampaignId($id))->recipients[self::ADA]);
    }

    public function testTheCampaignsOfTheAccountAreNamedAndFound(): void
    {
        $campaign = $this->aFrozenCampaign(self::ADA);
        $this->repository()->save($campaign);

        $this->assertSame([$campaign->id()->value => 'Relance figée'], $this->retriever()->namesOf($this->account, [$campaign->id(), new CampaignId(self::newIdentity())]));
        $this->assertSame([], $this->retriever()->namesOf(new AccountId(self::newIdentity()), [$campaign->id()]));
        $this->assertSame('Relance figée', $this->retriever()->find($this->account, $campaign->id())?->name);
        $this->assertNull($this->retriever()->find(new AccountId(self::newIdentity()), $campaign->id()));
    }

    /**
     * @return array<string, string>
     */
    private static function statusesOf(CampaignSheet $sheet): array
    {
        return array_map(static fn (RecipientInSheet $recipient): string => $recipient->status, $sheet->recipients);
    }

    public function testACampaignIsFoundInItsAccountOnly(): void
    {
        $campaign = $this->aCampaign('Relance ESN');
        $this->repository()->save($campaign);
        $campaign->releaseEvents();

        $this->assertEquals($campaign, $this->repository()->find($this->account, $campaign->id()));
        $this->assertNull($this->repository()->find(new AccountId(self::newIdentity()), $campaign->id()));
    }

    public function testTheCampaignsKeepingAContactAreFoundInItsAccount(): void
    {
        $keeping = $this->aFrozenCampaign(self::ADA);
        $this->repository()->save($keeping);
        $this->repository()->save($this->aFrozenCampaign(self::ALAN));
        $this->repository()->save($this->aCampaign('Brouillon'));

        $this->assertSame([$keeping->id()->value], array_map(static fn (Campaign $campaign): string => $campaign->id()->value, $this->repository()->withRecipient($this->account, new PersonId(self::ADA))));
        $this->assertSame([], $this->repository()->withRecipient(new AccountId(self::newIdentity()), new PersonId(self::ADA)));
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

    private function aFrozenCampaign(string ...$contacts): Campaign
    {
        $campaign = $this->aCampaign('Relance figée');
        $reached = ContactTags::of([Tag::written('#ESN')], [], new Reach(true, false, false, false));
        $campaign->freeze(array_fill_keys(array_values($contacts), $reached), new MemberId(self::newIdentity()), new \DateTimeImmutable('2026-11-17 11:00:00'));

        return $campaign;
    }

    private function repository(): CampaignRepository
    {
        return self::getContainer()->get(CampaignRepository::class);
    }

    private function retriever(): CampaignSheetRetriever
    {
        return self::getContainer()->get(CampaignSheetRetriever::class);
    }

    private function campaignsTable(): Table
    {
        $table = self::getContainer()->get('app.table.campaigns');
        $this->assertInstanceOf(Table::class, $table);

        return $table;
    }
}
