<?php

declare(strict_types=1);

namespace Tests\App\Campaigns;

use App\Shared\Persistence\ConditionFailed;
use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Campaigns\Application\FactRecorder;
use Prospecter\Core\Campaigns\Domain\Model\ContactFact;
use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\Campaigns\Domain\Model\Locality;
use Prospecter\Core\Campaigns\Domain\Model\OrganizationFact;
use Prospecter\Core\Campaigns\Domain\Model\OrganizationId;
use Prospecter\Core\Campaigns\Domain\Model\Reach;
use Prospecter\Core\Campaigns\Domain\Repository\FactRepository;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Tests\App\Base\ApiTestCase;

/**
 * The facts Campaigns keeps in its table (ADR 0141): an organization fact writes a link per contact it names and
 * removes those of the contacts it no longer names, under the condition that the fact it replaces is still the one
 * kept; a terminal fact is forgotten fifteen days later.
 */
class DynamoFactRepositoryTest extends ApiTestCase
{
    private AccountId $account;
    private OrganizationId $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->account = new AccountId(self::newIdentity());
        $this->organization = new OrganizationId(self::newIdentity());
    }

    public function testTheLinksFollowTheContactsTheOrganizationNames(): void
    {
        $ada = new ContactId(self::newIdentity());
        $grace = new ContactId(self::newIdentity());
        $this->recorder()->record($this->organizationNaming(1, $ada, $grace));

        $this->recorder()->record($this->organizationNaming(2, $grace));

        $this->assertSame([], $this->linksOf($ada));
        $this->assertSame([$this->organization->value], $this->linksOf($grace));
        $this->assertEquals($this->organizationNaming(2, $grace), $this->repository()->find($this->organizationNaming(0)));
    }

    public function testATerminalFactRemovesTheLinksAndIsForgottenFifteenDaysLater(): void
    {
        $ada = new ContactId(self::newIdentity());
        $this->recorder()->record($this->organizationNaming(1, $ada));

        $this->recorder()->record(new OrganizationFact($this->account, $this->organization, 0, false, [], Locality::unknown()));
        $this->recorder()->record($this->organizationNaming(2, $ada));

        $this->assertSame([], $this->linksOf($ada));
        $item = self::storedItem($this->campaignsTable(), 'ACCOUNT#'.$this->account->value, 'FACT#ORGANIZATION#'.$this->organization->value);
        $this->assertFalse($item['present']);
        $this->assertSame(new \DateTimeImmutable('2100-01-16 00:00:00 UTC')->getTimestamp(), $item['expiresAt']);
    }

    public function testAnOrganizationFactKeepsWhereTheOrganizationIs(): void
    {
        $fact = new OrganizationFact($this->account, $this->organization, 1, true, [], new Locality('67218', 'FR'));

        $this->recorder()->record($fact);

        $this->assertEquals($fact, $this->repository()->find($fact));
    }

    public function testAFactRebuiltAtTheSameRevisionReplacesTheKeptOne(): void
    {
        $this->recorder()->record($this->organizationNaming(4));
        $rebuilt = new OrganizationFact($this->account, $this->organization, 4, true, [], new Locality('67482', 'FR'));

        $this->recorder()->recordAgain($rebuilt);

        $this->assertEquals($rebuilt, $this->repository()->find($rebuilt));
    }

    public function testAContactFactKeepsHowTheContactIsReached(): void
    {
        $fact = new ContactFact($this->account, new ContactId(self::newIdentity()), 3, true, new Reach(true, true, true, false));

        $this->recorder()->record($fact);

        $this->assertEquals($fact, $this->repository()->find($fact));
    }

    public function testAFactChangedMeanwhileCancelsTheRecordingWrittenFromWhatWasRead(): void
    {
        $this->recorder()->record($this->organizationNaming(1));
        $read = $this->repository()->find($this->organizationNaming(0));
        $this->repository()->record($this->organizationNaming(2), $read);

        $this->expectException(ConditionFailed::class);

        $this->repository()->record($this->organizationNaming(3), $read);
    }

    private function organizationNaming(int $revision, ContactId ...$contacts): OrganizationFact
    {
        return new OrganizationFact($this->account, $this->organization, $revision, true, array_values($contacts), Locality::unknown());
    }

    /**
     * @return list<string> the organizations whose links name the contact
     */
    private function linksOf(ContactId $contact): array
    {
        $prefix = 'FACT#CONTACT#'.$contact->value.'#ORGANIZATION#';
        $links = $this->campaignsTable()->queryBetween('ACCOUNT#'.$this->account->value, null, $prefix, $prefix.'~');

        return Typed::strings(array_column($links, 'organization'));
    }

    private function recorder(): FactRecorder
    {
        return self::service(FactRecorder::class);
    }

    private function repository(): FactRepository
    {
        return self::service(FactRepository::class);
    }

    private function campaignsTable(): Table
    {
        return self::tableOf('campaigns');
    }
}
