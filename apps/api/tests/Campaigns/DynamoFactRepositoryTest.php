<?php

namespace Tests\App\Campaigns;

use App\Shared\Persistence\ConditionFailed;
use App\Shared\Persistence\Table;
use Prospecter\Core\Campaigns\Application\FactRecorder;
use Prospecter\Core\Campaigns\Domain\Model\ContactFact;
use Prospecter\Core\Campaigns\Domain\Model\Locality;
use Prospecter\Core\Campaigns\Domain\Model\OrganizationFact;
use Prospecter\Core\Campaigns\Domain\Model\Reach;
use Prospecter\Core\Campaigns\Domain\Repository\FactRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
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
        $ada = new PersonId(self::newIdentity());
        $grace = new PersonId(self::newIdentity());
        $this->recorder()->record($this->organizationNaming(1, $ada, $grace));

        $this->recorder()->record($this->organizationNaming(2, $grace));

        $this->assertSame([], $this->linksOf($ada));
        $this->assertSame([$this->organization->value], $this->linksOf($grace));
        $this->assertEquals($this->organizationNaming(2, $grace), $this->repository()->find($this->organizationNaming(0)));
    }

    public function testATerminalFactRemovesTheLinksAndIsForgottenFifteenDaysLater(): void
    {
        $ada = new PersonId(self::newIdentity());
        $this->recorder()->record($this->organizationNaming(1, $ada));

        $this->recorder()->record(new OrganizationFact($this->account, $this->organization, 0, false, [], Locality::unknown()));
        $this->recorder()->record($this->organizationNaming(2, $ada));

        $this->assertSame([], $this->linksOf($ada));
        $item = $this->campaignsTable()->item('ACCOUNT#'.$this->account->value, 'FACT#ORGANIZATION#'.$this->organization->value);
        $this->assertFalse($item['present']);
        $this->assertEqualsWithDelta(new \DateTimeImmutable('+15 days')->getTimestamp(), $item['expiresAt'], 60);
    }

    public function testAnOrganizationFactKeepsWhereTheOrganizationIs(): void
    {
        $fact = new OrganizationFact($this->account, $this->organization, 1, true, [], new Locality('67218', 'FR'));

        $this->recorder()->record($fact);

        $this->assertEquals($fact, $this->repository()->find($fact));
    }

    public function testAContactFactKeepsHowTheContactIsReached(): void
    {
        $fact = new ContactFact($this->account, new PersonId(self::newIdentity()), 3, true, new Reach(true, true, true, false));

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

    private function organizationNaming(int $revision, PersonId ...$contacts): OrganizationFact
    {
        return new OrganizationFact($this->account, $this->organization, $revision, true, array_values($contacts), Locality::unknown());
    }

    /**
     * @return list<string> the organizations whose links name the contact
     */
    private function linksOf(PersonId $contact): array
    {
        $prefix = 'FACT#CONTACT#'.$contact->value.'#ORGANIZATION#';
        $links = $this->campaignsTable()->queryBetween('ACCOUNT#'.$this->account->value, null, $prefix, $prefix.'~');

        return array_column($links, 'organization');
    }

    private function recorder(): FactRecorder
    {
        return self::getContainer()->get(FactRecorder::class);
    }

    private function repository(): FactRepository
    {
        return self::getContainer()->get(FactRepository::class);
    }

    private function campaignsTable(): Table
    {
        $table = self::getContainer()->get('app.table.campaigns');
        $this->assertInstanceOf(Table::class, $table);

        return $table;
    }
}
