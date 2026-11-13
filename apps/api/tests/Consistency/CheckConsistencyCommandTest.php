<?php

namespace Tests\App\Consistency;

use App\Demo\DemoData;
use App\Shared\Persistence\Table;
use Prospecter\Core\Accounts\Domain\Model\Account;
use Prospecter\Core\Accounts\Domain\Repository\AccountRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\App\Base\ApiTestCase;
use Tests\App\Double\InMemorySqs;

/**
 * `app:consistency:check` counts the figures again by reading the tables of the contexts, checks what the reactions
 * between the contexts should have written, and the queues of the failures: it lists each discrepancy with its
 * account, what it concerns and the command correcting it, and fails when there is one; it finds the accounts and the
 * exchanges by their items, so that one missing from its list is shown too. The demonstration: SOLEAN IT prospects
 * Babbage & Co, placed on the map, its contact Ada, and two exchanges with her in October 2026; its figures are counted
 * again before each test.
 */
class CheckConsistencyCommandTest extends ApiTestCase
{
    private const string INTEGRATION_FAILURES = 'prospecter-test-integration-failures';
    private const string RELAY_FAILURES = 'prospecter-test-relay-failures';

    private CommandTester $tester;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rebuildTheFigures();
        $this->tester = new CommandTester(new Application(self::$kernel)->find('app:consistency:check'));
    }

    public function testConsistentDataShowNoDiscrepancy(): void
    {
        $this->assertSame(Command::SUCCESS, $this->tester->execute([]));

        $this->assertStringContainsString('No discrepancy', $this->tester->getDisplay());
    }

    public function testAFigureFakedByHandIsShown(): void
    {
        $this->reportingTable()->batch([['PK' => 'FIGURES#1#'.DemoData::SOLEAN_IT, 'SK' => 'TOTAL', 'activeOrganizations' => 1, 'locatedOrganizations' => 1, 'contacts' => 5, 'exchanges' => 2]]);

        $this->assertDiscrepancy(DemoData::SOLEAN_IT, 'contacts', 'Reporting counts 5, the tables 1', 'app:reporting:rebuild');
    }

    public function testTheExchangesOfAMonthFakedByHandAreShown(): void
    {
        $this->reportingTable()->batch([['PK' => 'FIGURES#1#'.DemoData::SOLEAN_IT, 'SK' => 'MONTH#2026-10', 'exchanges' => 3]]);

        $this->assertDiscrepancy(DemoData::SOLEAN_IT, 'exchanges of 2026-10', 'Reporting counts 3, the tables 2', 'app:reporting:rebuild');
    }

    public function testFiguresNeverCountedAreShown(): void
    {
        $this->reportingTable()->batch([], [['PROJECTION', 'ACTIVE']]);

        $this->assertDiscrepancy(DemoData::SOLEAN_IT, 'activeOrganizations', 'Reporting counts 0, the tables 1', 'app:reporting:rebuild');
    }

    public function testACollectedContactNotAttachedToItsOrganizationsIsShown(): void
    {
        $babbage = $this->babbage();
        $this->prospectingTable()->batch([['organization' => ['contacts' => []] + $babbage['organization']] + $babbage]);

        $this->assertDiscrepancy(DemoData::SOLEAN_IT, $this->ada(), 'contact attached to no organization', 'app:integration:redrive');
    }

    public function testAForgottenContactStillAttachedIsShown(): void
    {
        $ada = $this->ada();
        $this->personalDataTable()->batch([], [['PERSON#'.$ada, 'PERSON']]);

        $this->assertDiscrepancy(DemoData::SOLEAN_IT, $ada, 'forgotten contact still attached to '.$this->babbage()['organization']['id'], 'app:integration:redrive');
    }

    public function testAMemberWithoutProfileIsShown(): void
    {
        $profile = $this->accountsTable()->item('MEMBER#'.DemoData::NEWCOMER, 'MEMBER')['member']['personId'];
        $this->personalDataTable()->batch([], [['PERSON#'.$profile, 'PERSON']]);

        $this->assertDiscrepancy(DemoData::SOLEAN_IT, DemoData::NEWCOMER, 'member without their profile '.$profile, 'app:integration:redrive');
    }

    public function testAnAccountWithoutMemberIsShown(): void
    {
        $initech = $this->anAccountWithoutMember();

        $this->assertDiscrepancy($initech, $initech, 'account without member: its founder is not registered', 'app:integration:redrive');
    }

    public function testAnExchangeKeptForAnOrganizationNoLongerProspectedIsShown(): void
    {
        $babbage = $this->babbage()['organization']['id'];
        $this->prospectingTable()->batch([], [['ORGANIZATION#'.$babbage, 'ORGANIZATION']]);

        $this->assertDiscrepancy(DemoData::SOLEAN_IT, $this->anExchange(), 'exchange kept for '.$babbage.', no longer prospected', 'app:integration:redrive');
    }

    public function testAnAccountOutOfTheListOfTheAccountsIsShown(): void
    {
        $soleanIt = $this->accountsTable()->item('ACCOUNT#'.DemoData::SOLEAN_IT, 'ACCOUNT');
        unset($soleanIt['GSI1PK'], $soleanIt['GSI1SK']);
        $this->accountsTable()->batch([$soleanIt]);

        $this->assertDiscrepancy(DemoData::SOLEAN_IT, DemoData::SOLEAN_IT, 'account out of the list of the accounts', 'app:keys:rebuild');
    }

    public function testOnlyTheExchangesThatTookPlaceAreCounted(): void
    {
        $exchanges = $this->prospectingTable()->query('ACCOUNT_EXCHANGES#'.DemoData::SOLEAN_IT, Table::BY_GSI2);
        $exchanges[0]['exchange']['status'] = 'planned';
        $exchanges[1]['exchange']['status'] = 'cancelled';
        $this->prospectingTable()->batch($exchanges);
        $this->rebuildTheFigures();

        $this->assertSame(Command::SUCCESS, $this->tester->execute([]));
    }

    public function testAnExchangeOutOfTheListOfItsAccountIsShown(): void
    {
        $exchange = $this->anExchange();
        $item = $this->prospectingTable()->item('EXCHANGE#'.$exchange, 'EXCHANGE');
        unset($item['GSI2PK'], $item['GSI2SK']);
        $this->prospectingTable()->batch([$item]);

        $this->assertDiscrepancy(DemoData::SOLEAN_IT, $exchange, 'exchange out of the list of its account', 'app:keys:rebuild');
    }

    public function testAnEventTheWorkerFailedToApplyIsShown(): void
    {
        $this->queue()->send(self::INTEGRATION_FAILURES, '{"requestPayload": {}}');

        $this->assertDiscrepancy('', self::INTEGRATION_FAILURES, '1 message(s) waiting', 'app:integration:redrive');
    }

    public function testAnEventTheRelayFailedToPublishIsShown(): void
    {
        $this->queue()->send(self::RELAY_FAILURES, '{"requestContext": {}}');

        $this->assertDiscrepancy('', self::RELAY_FAILURES, '1 message(s) waiting', 'app:outbox:republish');
    }

    public function testTheOptionAccountChecksThatAccountAlone(): void
    {
        $this->anAccountWithoutMember();

        $this->assertSame(Command::SUCCESS, $this->tester->execute(['--account' => DemoData::SOLEAN_IT]));
    }

    public function testAnUnknownAccountIsRefused(): void
    {
        $this->assertSame(Command::INVALID, $this->tester->execute(['--account' => self::newIdentity()]));

        $this->assertStringContainsString('No account', $this->tester->getDisplay());
    }

    public function testTheCheckWritesNothing(): void
    {
        $before = $this->everyTable();

        $this->tester->execute([]);

        $this->assertEquals($before, $this->everyTable());
    }

    private function assertDiscrepancy(string $account, string $concerns, string $discrepancy, string $correction): void
    {
        $this->assertSame(Command::FAILURE, $this->tester->execute([]));

        $display = preg_replace('/\s+/', ' ', $this->tester->getDisplay());
        $this->assertMatchesRegularExpression(sprintf('/%s %s %s .*%s/', preg_quote($account, '/'), preg_quote($concerns, '/'), preg_quote($discrepancy, '/'), preg_quote($correction, '/')), $display);
    }

    private function rebuildTheFigures(): void
    {
        $tester = new CommandTester(new Application(self::$kernel)->find('app:reporting:rebuild'));
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();
    }

    private function anAccountWithoutMember(): string
    {
        $initech = self::newIdentity();
        self::getContainer()->get(AccountRepository::class)->save(Account::open(new AccountId($initech), new LegalIdentity('Initech', 'SAS'), new \DateTimeImmutable('2026-10-14 09:00')));

        return $initech;
    }

    /**
     * @return array<string, mixed> the item of the organization of the demonstration
     */
    private function babbage(): array
    {
        return $this->prospectingTable()->query('ACCOUNT#'.DemoData::SOLEAN_IT, Table::BY_GSI1)[0];
    }

    private function ada(): string
    {
        return $this->personalDataTable()->query('ACCOUNT#'.DemoData::SOLEAN_IT.'#prospecting', Table::BY_GSI1)[0]['person']['id'];
    }

    private function anExchange(): string
    {
        return $this->prospectingTable()->query('ACCOUNT_EXCHANGES#'.DemoData::SOLEAN_IT, Table::BY_GSI2)[0]['exchange']['id'];
    }

    private function queue(): InMemorySqs
    {
        return self::getContainer()->get(InMemorySqs::class);
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function everyTable(): array
    {
        return array_map(static fn (Table $table): array => $table->scan(''), [
            'accounts' => $this->accountsTable(),
            'personal_data' => $this->personalDataTable(),
            'prospecting' => $this->prospectingTable(),
            'reporting' => $this->reportingTable(),
        ]);
    }

    private function accountsTable(): Table
    {
        return $this->table('accounts');
    }

    private function personalDataTable(): Table
    {
        return $this->table('personal_data');
    }

    private function prospectingTable(): Table
    {
        return $this->table('prospecting');
    }

    private function reportingTable(): Table
    {
        return $this->table('reporting');
    }

    private function table(string $context): Table
    {
        $table = self::getContainer()->get('app.table.'.$context);
        $this->assertInstanceOf(Table::class, $table);

        return $table;
    }
}
