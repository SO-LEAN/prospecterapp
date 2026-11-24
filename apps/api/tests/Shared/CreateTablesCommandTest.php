<?php

declare(strict_types=1);

namespace Tests\App\Shared;

use App\Demo\DemoData;
use Prospecter\Core\Reporting\Application\ConsultFigures\FiguresResponse;
use Prospecter\Core\Reporting\Application\Port\FiguresRetriever;
use Prospecter\Core\Reporting\Application\Port\ProjectionVersions;
use Prospecter\Core\Reporting\Domain\Model\Month;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\App\Base\ApiTestCase;

/**
 * `app:tables:recreate --demo` recreates the tables with the demonstration (`make db`) and counts its figures without
 * waiting for the worker: no relay reads the new tables yet (ADR 0209). The next test gets the demonstration back
 * (ADR 0180).
 */
class CreateTablesCommandTest extends ApiTestCase
{
    public function testTheDemonstrationRecreatedHasItsFiguresCounted(): void
    {
        $this->recreateWithTheDemonstration();

        $this->assertEquals(new FiguresResponse(activeOrganizations: 1, locatedOrganizations: 1, contacts: 2, exchanges: 2, exchangesThisMonth: 2), $this->figuresOfTheDemonstration());
    }

    public function testTheFiguresAreCountedWithoutWaitingForTheWorker(): void
    {
        $before = $this->clock()->now();

        $this->recreateWithTheDemonstration();

        $this->assertEquals($before, $this->clock()->now());
    }

    private function recreateWithTheDemonstration(): void
    {
        $tester = new CommandTester(new Application(self::$kernel ?? self::bootKernel())->find('app:tables:recreate'));
        $tester->execute(['--demo' => true]);
        $tester->assertCommandIsSuccessful();
    }

    private function clock(): MockClock
    {
        return self::service(MockClock::class, 'clock');
    }

    /**
     * Ada and Grace, the contact to attach that the stack's demonstration adds, are both counted.
     */
    private function figuresOfTheDemonstration(): FiguresResponse
    {
        $versions = self::service(ProjectionVersions::class);
        $this->assertNotNull($versions->active(), 'The figures are counted');

        return self::service(FiguresRetriever::class)->of($versions->active(), new AccountId(DemoData::SOLEAN_IT), Month::of(new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS)));
    }
}
