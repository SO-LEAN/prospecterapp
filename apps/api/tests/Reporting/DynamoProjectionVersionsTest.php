<?php

namespace Tests\App\Reporting;

use App\Shared\Persistence\Table;
use Prospecter\Core\Reporting\Application\FactRecorder;
use Prospecter\Core\Reporting\Application\Port\ProjectionVersions;
use Prospecter\Core\Reporting\Domain\Model\OrganizationFact;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Tests\App\Base\ApiTestCase;

/**
 * The versions of the figures in the table of Reporting: the version a rebuild was building is abandoned before the
 * abandoned versions are emptied, so that no recording writes in a version while it is emptied (ADR 0106).
 */
class DynamoProjectionVersionsTest extends ApiTestCase
{
    private const int ACTIVE = 1;
    private const int ABANDONED = 2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->versions()->startBuilding(self::ACTIVE);
        $this->versions()->activate(self::ACTIVE);
        $this->versions()->startBuilding(self::ABANDONED);
    }

    public function testAVersionAbandonedIsNoLongerLive(): void
    {
        $this->versions()->abandonBuilding();

        $this->assertSame([self::ACTIVE], $this->versions()->live());
        $this->assertSame(self::ACTIVE, $this->versions()->active());
    }

    public function testAFactReceivedOnceTheVersionIsAbandonedIsRecordedInTheActiveOneOnly(): void
    {
        $this->versions()->abandonBuilding();

        $this->recordAnOrganization();

        $this->assertCount(1, $this->reportingTable()->scan('FACTS#'.self::ACTIVE.'#'));
        $this->assertSame([], $this->reportingTable()->scan('FACTS#'.self::ABANDONED.'#'));
    }

    public function testRemovingTheAbandonedVersionsKeepsTheActiveOne(): void
    {
        $this->recordAnOrganization();
        $this->versions()->abandonBuilding();

        $this->versions()->removeAbandoned();

        $this->assertSame([], [...$this->reportingTable()->scan('FACTS#'.self::ABANDONED.'#'), ...$this->reportingTable()->scan('FIGURES#'.self::ABANDONED.'#')]);
        $this->assertCount(1, $this->reportingTable()->scan('FACTS#'.self::ACTIVE.'#'));
        $this->assertCount(1, $this->reportingTable()->scan('FIGURES#'.self::ACTIVE.'#'));
    }

    public function testStartingABuildRemovesNothing(): void
    {
        $this->recordAnOrganization();
        $this->versions()->abandonBuilding();

        $this->versions()->startBuilding(self::ABANDONED);

        $this->assertCount(1, $this->reportingTable()->scan('FACTS#'.self::ABANDONED.'#'));
    }

    private function recordAnOrganization(): void
    {
        self::getContainer()->get(FactRecorder::class)->record(new OrganizationFact(new AccountId(self::newIdentity()), new OrganizationId(self::newIdentity()), 1, true, false));
    }

    private function versions(): ProjectionVersions
    {
        return self::getContainer()->get(ProjectionVersions::class);
    }

    private function reportingTable(): Table
    {
        $table = self::getContainer()->get('app.table.reporting');
        $this->assertInstanceOf(Table::class, $table);

        return $table;
    }
}
