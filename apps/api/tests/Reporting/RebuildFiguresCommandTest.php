<?php

namespace Tests\App\Reporting;

use App\Demo\DemoData;
use App\Shared\Persistence\ConditionFailed;
use App\Shared\Persistence\Table;
use Prospecter\Core\Accounts\Domain\Model\Account;
use Prospecter\Core\Accounts\Domain\Repository\AccountRepository;
use Prospecter\Core\Reporting\Application\ConsultFigures\FiguresResponse;
use Prospecter\Core\Reporting\Application\Port\FiguresRetriever;
use Prospecter\Core\Reporting\Application\Port\ProjectionVersions;
use Prospecter\Core\Reporting\Domain\Model\Month;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\App\Base\ApiTestCase;
use Tests\App\Double\ConflictingFactRepository;

/**
 * `app:reporting:rebuild` counts every account again from the state of the other contexts, in a new version made
 * the active one, the former one removed; it writes only the table of Reporting, and says how many accounts it counted,
 * none included. The demonstration: SOLEAN IT
 * prospects Babbage & Co, placed on the map, its contact Ada, and two exchanges with her in October 2026.
 */
class RebuildFiguresCommandTest extends ApiTestCase
{
    public function testTheRebuildOfTheDemonstrationGivesTheFiguresCountedAgain(): void
    {
        $this->rebuild();

        $this->assertSame(1, $this->versions()->active());
        $this->assertEquals(new FiguresResponse(activeOrganizations: 1, locatedOrganizations: 1, contacts: 1, exchanges: 2, exchangesThisMonth: 2), $this->figuresOfTheDemonstration());
    }

    public function testARebuildRemovesTheFormerVersion(): void
    {
        $this->rebuild();
        $this->login();
        $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Initech']]);

        $this->rebuild();

        $this->assertSame([2], $this->versions()->live());
        $this->assertSame([], $this->reportingTable()->scan('FACTS#1#'));
        $this->assertSame([], $this->reportingTable()->scan('FIGURES#1#'));
        $this->assertEquals(new FiguresResponse(activeOrganizations: 2, locatedOrganizations: 1, contacts: 1, exchanges: 2, exchangesThisMonth: 2), $this->figuresOfTheDemonstration());
    }

    public function testARebuildCancelledByAConflictStartsAgain(): void
    {
        self::getContainer()->get(ConflictingFactRepository::class)->conflict(times: 3);

        $this->rebuild();

        $this->assertSame([1], $this->versions()->live());
        $this->assertEquals(new FiguresResponse(activeOrganizations: 1, locatedOrganizations: 1, contacts: 1, exchanges: 2, exchangesThisMonth: 2), $this->figuresOfTheDemonstration());
    }

    public function testARebuildCancelledBeyondThreeRetriesFails(): void
    {
        self::getContainer()->get(ConflictingFactRepository::class)->conflict(times: 4);

        $this->expectException(ConditionFailed::class);

        $this->rebuild();
    }

    public function testARebuildRemovesWhatARecordingWroteInAFormerVersion(): void
    {
        $this->rebuild();
        $this->reportingTable()->batch([['PK' => 'FIGURES#7#'.DemoData::SOLEAN_IT, 'SK' => 'TOTAL', 'contacts' => 1]]);

        $this->rebuild();

        $this->assertSame([], $this->reportingTable()->scan('FIGURES#7#'));
    }

    public function testARebuildWritesOnlyTheTableOfReporting(): void
    {
        $before = $this->otherTables();

        $this->rebuild();

        $this->assertEquals($before, $this->otherTables());
    }

    public function testTheRebuildSaysHowManyAccountsItCounted(): void
    {
        self::getContainer()->get(AccountRepository::class)->save(Account::open(new AccountId(self::newIdentity()), new LegalIdentity('Initech', 'SAS'), new \DateTimeImmutable('2026-10-14 09:00')));

        $this->assertStringContainsString('counted again for 2 account(s)', $this->rebuild());
    }

    public function testARebuildWithoutAccountSaysSoAndSucceeds(): void
    {
        $this->accountsTable()->batch([], [['ACCOUNT#'.DemoData::SOLEAN_IT, 'ACCOUNT']]);

        $this->assertStringContainsString('counted again for 0 account(s)', $this->rebuild());
    }

    private function rebuild(): string
    {
        $tester = new CommandTester(new Application(self::$kernel)->find('app:reporting:rebuild'));
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();

        return preg_replace('/\s+/', ' ', $tester->getDisplay());
    }

    private function figuresOfTheDemonstration(): FiguresResponse
    {
        return self::getContainer()->get(FiguresRetriever::class)->of((int) $this->versions()->active(), new AccountId(DemoData::SOLEAN_IT), $this->thisMonth());
    }

    private function thisMonth(): Month
    {
        return Month::of(new \DateTimeImmutable('2026-10-15 09:00:00 UTC'));
    }

    private function versions(): ProjectionVersions
    {
        return self::getContainer()->get(ProjectionVersions::class);
    }

    /**
     * @return array<string, list<array<string, mixed>>> the items of the tables of the other contexts
     */
    private function otherTables(): array
    {
        $items = [];

        foreach (['accounts', 'personal_data', 'prospecting'] as $context) {
            $items[$context] = $this->table($context)->scan('');
        }

        return $items;
    }

    private function reportingTable(): Table
    {
        return $this->table('reporting');
    }

    private function accountsTable(): Table
    {
        return $this->table('accounts');
    }

    private function table(string $context): Table
    {
        $table = self::getContainer()->get('app.table.'.$context);
        $this->assertInstanceOf(Table::class, $table);

        return $table;
    }
}
