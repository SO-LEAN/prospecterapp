<?php

namespace Tests\App\Reporting;

use App\Reporting\Persistence\DynamoFactRepository;
use App\Reporting\Persistence\ReportingKeys;
use App\Shared\Identities;
use App\Shared\Messaging\CommitOutbox;
use App\Shared\Persistence\ConditionFailed;
use App\Shared\Persistence\DynamoUnitOfWork;
use App\Shared\Persistence\Table;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Prospecter\Core\Reporting\Application\ConsultFigures\FiguresResponse;
use Prospecter\Core\Reporting\Application\FactRecorder;
use Prospecter\Core\Reporting\Application\Port\FiguresRetriever;
use Prospecter\Core\Reporting\Application\Port\ProjectionVersions;
use Prospecter\Core\Reporting\Domain\Model\ExchangeFact;
use Prospecter\Core\Reporting\Domain\Model\ExchangeId;
use Prospecter\Core\Reporting\Domain\Model\FiguresChange;
use Prospecter\Core\Reporting\Domain\Model\Month;
use Prospecter\Core\Reporting\Domain\Model\OrganizationFact;
use Prospecter\Core\Reporting\Domain\Repository\FactRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Symfony\Component\Clock\NativeClock;
use Tests\App\Base\ApiTestCase;

/**
 * The facts of a version and the figures they make, in the table of Reporting: a fact replaces the one the version
 * keeps under the condition that it is still there, its change added to the figures, so that facts recorded at the
 * same time all count.
 */
class DynamoFactRepositoryTest extends ApiTestCase
{
    private const int VERSION = 1;
    private const string SEPTEMBER = '2026-09-10T09:00:00Z';
    private const string OCTOBER = '2026-10-10T09:00:00Z';

    private AccountId $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->account = new AccountId(self::newIdentity());
        $versions = self::getContainer()->get(ProjectionVersions::class);
        $versions->startBuilding(self::VERSION);
        $versions->activate(self::VERSION);
    }

    public function testAnOlderRevisionIsIgnored(): void
    {
        $organization = new OrganizationId(self::newIdentity());
        $this->recorder()->record($this->organization($organization, revision: 2, located: true));

        $this->recorder()->record($this->organization($organization, revision: 1, located: false));

        $this->assertEquals($this->organization($organization, revision: 2, located: true), $this->repository()->find(self::VERSION, $this->organization($organization, 0)));
        $this->assertSame([1, 1], [$this->figures()->activeOrganizations, $this->figures()->locatedOrganizations]);
    }

    public function testATerminalFactWinsAndIsForgottenFifteenDaysLater(): void
    {
        $organization = new OrganizationId(self::newIdentity());
        $this->recorder()->record($this->organization($organization, revision: 3, located: true));

        $this->recorder()->record(new OrganizationFact($this->account, $organization, 0, false, false));
        $this->recorder()->record($this->organization($organization, revision: 4, located: true));

        $item = $this->reportingTable()->item(ReportingKeys::facts(self::VERSION, $this->account), 'ORGANIZATION#'.$organization->value);
        $this->assertFalse($item['present']);
        $this->assertEqualsWithDelta(new \DateTimeImmutable('+15 days')->getTimestamp(), $item['expiresAt'], 60);
        $this->assertSame([0, 0], [$this->figures()->activeOrganizations, $this->figures()->locatedOrganizations]);
    }

    public function testTwoRecordingsOnTheSameAccountAtTheSameTimeBothCount(): void
    {
        $unitOfWork = self::getContainer()->get(DynamoUnitOfWork::class);
        $unitOfWork->begin();
        $this->recorder()->record($this->organization(new OrganizationId(self::newIdentity()), revision: 1));
        $this->asAnotherRequest()->record(self::VERSION, $this->organization(new OrganizationId(self::newIdentity()), revision: 1), null, new FiguresChange(activeOrganizations: 1));
        $unitOfWork->commit();
        $unitOfWork->discard();

        $this->assertSame(2, $this->figures()->activeOrganizations);
    }

    public function testAFactChangedMeanwhileCancelsTheRecordingWrittenFromWhatWasRead(): void
    {
        $organization = new OrganizationId(self::newIdentity());
        $this->recorder()->record($this->organization($organization, revision: 1));
        $read = $this->repository()->find(self::VERSION, $this->organization($organization, 0));
        $this->asAnotherRequest()->record(self::VERSION, $this->organization($organization, revision: 2, located: true), $read, new FiguresChange(locatedOrganizations: 1));

        try {
            $this->repository()->record(self::VERSION, $this->organization($organization, revision: 3), $read, FiguresChange::none());
            $this->fail('The recording was expected to be cancelled');
        } catch (ConditionFailed) {
            $this->assertSame(2, $this->repository()->find(self::VERSION, $this->organization($organization, 0))?->revision);
        }
    }

    public function testAnExchangeMovedToAnotherMonthLeavesTheFormerOne(): void
    {
        $exchange = new ExchangeId(self::newIdentity());
        $this->recorder()->record(new ExchangeFact($this->account, $exchange, 1, true, new \DateTimeImmutable(self::SEPTEMBER)));

        $this->recorder()->record(new ExchangeFact($this->account, $exchange, 2, true, new \DateTimeImmutable(self::OCTOBER)));

        $this->assertSame([1, 0], [$this->figures()->exchanges, $this->figures(self::SEPTEMBER)->exchangesThisMonth]);
        $this->assertSame(1, $this->figures(self::OCTOBER)->exchangesThisMonth);
    }

    public function testSeveralFactsOfAnAccountInOneUnitAllCount(): void
    {
        $unitOfWork = self::getContainer()->get(DynamoUnitOfWork::class);
        $unitOfWork->begin();
        $this->recorder()->record(new ExchangeFact($this->account, new ExchangeId(self::newIdentity()), 1, true, new \DateTimeImmutable(self::OCTOBER)));
        $this->recorder()->record(new ExchangeFact($this->account, new ExchangeId(self::newIdentity()), 1, true, new \DateTimeImmutable(self::OCTOBER)));
        $unitOfWork->commit();
        $unitOfWork->discard();

        $this->assertSame([2, 2], [$this->figures(self::OCTOBER)->exchanges, $this->figures(self::OCTOBER)->exchangesThisMonth]);
    }

    private function organization(OrganizationId $id, int $revision, bool $located = false): OrganizationFact
    {
        return new OrganizationFact($this->account, $id, $revision, true, $located);
    }

    private function figures(string $at = self::OCTOBER): FiguresResponse
    {
        return self::getContainer()->get(FiguresRetriever::class)->of(self::VERSION, $this->account, Month::of(new \DateTimeImmutable($at)));
    }

    private function recorder(): FactRecorder
    {
        return self::getContainer()->get(FactRecorder::class);
    }

    private function repository(): FactRepository
    {
        return self::getContainer()->get(FactRepository::class);
    }

    private function reportingTable(): Table
    {
        $table = self::getContainer()->get('app.table.reporting');
        $this->assertInstanceOf(Table::class, $table);

        return $table;
    }

    /**
     * The repository with the unit of work of another request: what it writes is sent at once.
     */
    private function asAnotherRequest(): DynamoFactRepository
    {
        $client = self::getContainer()->get(DynamoDbClient::class);
        $marshaler = self::getContainer()->get(Marshaler::class);
        $unitOfWork = new DynamoUnitOfWork($client, new CommitOutbox([], $marshaler, new Identities(), new NativeClock()));

        return new DynamoFactRepository(new Table($client, $marshaler, $unitOfWork, $this->reportingTable()->name), new NativeClock());
    }
}
