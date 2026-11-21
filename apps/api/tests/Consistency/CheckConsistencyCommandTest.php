<?php

namespace Tests\App\Consistency;

use App\Demo\DemoData;
use App\Shared\Persistence\Table;
use Prospecter\Core\Accounts\Domain\Model\Account;
use Prospecter\Core\Accounts\Domain\Repository\AccountRepository;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\Connection;
use Prospecter\Core\Connectors\Domain\Model\Grant;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;
use Prospecter\Core\Connectors\Domain\Model\Scope;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonName;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\ContactToAttach;
use Prospecter\Core\Prospecting\Domain\Model\Direction;
use Prospecter\Core\Prospecting\Domain\Model\Duration;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Repository\ContactToAttachRepository;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Designated;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\ExternalReferences;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
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
    /** where Babbage & Co is, in the demonstration */
    private const string COLMAR = '68066';

    private CommandTester $tester;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rebuildTheFigures();
        $this->rebuildTheTags();
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

    /**
     * A contact to attach is attached to no organization, their meeting kept for none, until their day (ADR 0146).
     */
    public function testAContactToAttachWaitingWithTheirMeetingIsNoDiscrepancy(): void
    {
        $this->aContactToAttachMetOn('2026-10-14 10:00 Europe/Paris');
        $this->rebuildTheFigures();
        $this->rebuildTheTags();

        $this->assertSame(Command::SUCCESS, $this->tester->execute([]), $this->tester->getDisplay());
    }

    /**
     * One whose day has passed shows the erasure the schedule missed (ADR 0147).
     */
    public function testAContactToAttachPastTheDayOfTheirErasureIsShown(): void
    {
        $person = $this->aContactToAttachMetOn('2026-09-01 10:00 Europe/Paris');

        $this->assertDiscrepancy(DemoData::SOLEAN_IT, $person, 'contact to attach past the day of their erasure, not erased', 'app:contacts:erase-unattached');
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

    public function testTheTagsOfAnOrganizationNoLongerProspectedAreShown(): void
    {
        $gone = self::newIdentity();
        $this->campaignsTable()->batch([$this->taggingItem('organization', $gone)]);

        $this->assertDiscrepancy(DemoData::SOLEAN_IT, $gone, 'tags kept for an organization no longer prospected', 'app:integration:redrive');
    }

    public function testTheTagsOfAForgottenContactAreShown(): void
    {
        $forgotten = self::newIdentity();
        $this->campaignsTable()->batch([$this->taggingItem('contact', $forgotten)]);

        $this->assertDiscrepancy(DemoData::SOLEAN_IT, $forgotten, 'tags kept for a forgotten contact', 'app:integration:redrive');
    }

    public function testARecipientKeptForAForgottenContactIsShown(): void
    {
        $forgotten = self::newIdentity();
        $campaign = self::newIdentity();
        $this->campaignsTable()->batch([$this->frozenCampaignItem($campaign, $forgotten)]);

        $this->assertDiscrepancy(DemoData::SOLEAN_IT, $campaign, 'recipient kept for a forgotten contact '.$forgotten, 'app:integration:redrive');
    }

    public function testAnErasedRecipientAStartedCampaignKeepsIsNoDiscrepancy(): void
    {
        $this->campaignsTable()->batch([$this->startedCampaignItem(self::newIdentity(), self::newIdentity())]);

        $this->assertSame(Command::SUCCESS, $this->tester->execute([]));
    }

    public function testARecipientErasedOnceProcessedIsNoDiscrepancy(): void
    {
        $this->campaignsTable()->batch([$this->startedCampaignItem(self::newIdentity(), self::newIdentity(), ['status' => 'processed', 'missed' => true, 'erased' => true])]);

        $this->assertSame(Command::SUCCESS, $this->tester->execute([]));
    }

    public function testTheTagsOfWhatTheAccountKeepsAreNoDiscrepancy(): void
    {
        $this->campaignsTable()->batch([$this->taggingItem('organization', $this->babbage()['organization']['id']), $this->taggingItem('contact', $this->ada())]);

        $this->assertSame(Command::SUCCESS, $this->tester->execute([]));
    }

    public function testAnOrganizationWhoseContactsCampaignsKeepsOutOfDateIsShown(): void
    {
        $babbage = $this->babbage()['organization']['id'];
        $this->campaignsTable()->batch([$this->organizationFactItem($babbage, [])]);

        $this->assertDiscrepancy(DemoData::SOLEAN_IT, $babbage, 'contacts of the organization out of date in Campaigns', 'app:tags:rebuild --account=<account>');
    }

    public function testAnOrganizationWhoseLocalityCampaignsKeepsOutOfDateIsShown(): void
    {
        $babbage = $this->babbage()['organization'];
        $this->campaignsTable()->batch([$this->organizationFactItem($babbage['id'], $babbage['contacts'], null)]);

        $this->assertDiscrepancy(DemoData::SOLEAN_IT, $babbage['id'], 'locality of the organization out of date in Campaigns', 'app:tags:rebuild --account=<account>');
    }

    public function testAContactWhoseReachCampaignsLacksIsShown(): void
    {
        $ada = $this->ada();
        $this->campaignsTable()->batch([], [['ACCOUNT#'.DemoData::SOLEAN_IT, 'FACT#CONTACT#'.$ada]]);

        $this->assertDiscrepancy(DemoData::SOLEAN_IT, $ada, 'reach of the contact out of date in Campaigns', 'app:tags:rebuild --account=<account>');
    }

    public function testTheFactsKeptInCampaignsForAnOrganizationNoLongerProspectedAreShown(): void
    {
        $gone = self::newIdentity();
        $this->campaignsTable()->batch([$this->organizationFactItem($gone, [$this->ada()])]);

        $this->assertDiscrepancy(DemoData::SOLEAN_IT, $gone, 'facts kept in Campaigns for an organization no longer prospected', 'app:integration:redrive');
    }

    public function testTheFactsKeptInCampaignsForAForgottenContactAreShown(): void
    {
        $forgotten = self::newIdentity();
        $this->campaignsTable()->batch([['PK' => 'ACCOUNT#'.DemoData::SOLEAN_IT, 'SK' => 'FACT#CONTACT#'.$forgotten, 'revision' => 1, 'present' => true, 'hasEmail' => true, 'hasPhone' => false, 'hasMobile' => false, 'hasLinkedIn' => false]]);

        $this->assertDiscrepancy(DemoData::SOLEAN_IT, $forgotten, 'facts kept in Campaigns for a forgotten contact', 'app:integration:redrive');
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

    public function testAnExchangeToComeOfAMemberConnectedNotSyncedInTheirCalendarIsShown(): void
    {
        $this->connectTheProspectorToGoogle();
        $planned = $this->anExchangePlannedByTheProspectorOn('2026-10-20 14:00 UTC');
        $this->anExchangePlannedByTheProspectorOn('2026-10-14 14:00 UTC');

        $this->assertSame(Command::FAILURE, $this->tester->execute([]));
        $this->assertSame(1, substr_count($this->tester->getDisplay(), 'not synced in the calendar of its holder'));
        $this->assertDiscrepancy(DemoData::SOLEAN_IT, $planned, 'exchange to come not synced in the calendar of its holder at google', 'its holder pushes it again from Prospecter once failed');
    }

    /**
     * Two contacts noted the same profile before it was unique: the members merge them or correct one by hand.
     */
    public function testTwoContactsNotingTheSameLinkAreShown(): void
    {
        $ada = $this->ada();
        $grace = $this->aContactNotingTheProfilesOfAda('https://www.linkedin.com/in/ada-lovelace');

        $this->assertDiscrepancy(DemoData::SOLEAN_IT, implode(', ', [$ada, $grace]), 'contacts noting the same link', 'the members merge them or correct one of them');
    }

    public function testAContactNotingTheProfileOfAnotherAsAFormerOneIsShown(): void
    {
        $ada = $this->ada();
        $grace = $this->aContactNotingTheProfilesOfAda('https://www.linkedin.com/in/ada-lovelace', 'https://www.linkedin.com/in/grace-hopper');

        $this->assertDiscrepancy(DemoData::SOLEAN_IT, implode(', ', [$ada, $grace]), 'contacts noting the same link', 'the members merge them or correct one of them');
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

    /**
     * A contact noting, before it was unique, the profile of Ada in the demonstration, then the others given.
     */
    private function aContactNotingTheProfilesOfAda(string ...$profiles): string
    {
        $grace = Person::collect(new PersonId(self::newIdentity()), new AccountId(DemoData::SOLEAN_IT), Purpose::Prospecting, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));

        foreach ($profiles as $profile) {
            $grace->changeContactDetails(new ContactDetails(), ExternalReferences::designating([$profile], Designated::Person), by: new Reader(new MemberId(DemoData::PROSPECTOR), new AccountId(DemoData::SOLEAN_IT), $grace->id(), false), at: new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        }
        $this->personalDataTable()->batch([], [[sprintf('REFERENCE#%s#linkedInProfile#ada-lovelace', DemoData::SOLEAN_IT), 'REFERENCE']]);
        self::getContainer()->get(PersonRepository::class)->save($grace);

        return $grace->id()->value;
    }

    private function aContactToAttachMetOn(string $moment): string
    {
        $member = new MemberId(DemoData::PROSPECTOR);
        $account = new AccountId(DemoData::SOLEAN_IT);
        $met = new \DateTimeImmutable($moment);
        $person = Person::collectToAttach(new Reader($member, $account, new PersonId(self::newIdentity()), true), new PersonId(self::newIdentity()), new PersonName('Grace', 'Hopper'), new ContactDetails(), $met);
        self::getContainer()->get(PersonRepository::class)->save($person);
        self::getContainer()->get(ContactToAttachRepository::class)->save(ContactToAttach::metBy(new Prospector($member, $account, managesPersonalData: true), $person->id(), $met, $met));
        $meeting = Exchange::notedWithAContact($account, $member, new ExchangeId(self::newIdentity()), $person->id(), [], new ExchangeFacts(Channel::VideoCall, Direction::Outbound, $met), ExchangeStatus::Done, $met);
        self::getContainer()->get(ExchangeRepository::class)->save($meeting);

        return $person->id()->value;
    }

    private function connectTheProspectorToGoogle(): void
    {
        $grant = new Grant(new Email('prospector@exemple.fr'), new RefreshToken('a-refresh-token'), Scope::Calendar);
        $connection = Connection::establish(new MemberId(DemoData::PROSPECTOR), new AccountId(DemoData::SOLEAN_IT), new ClientCredentials(Provider::Google, 'client-of-the-double', 'secret-of-the-double'), $grant, new \DateTimeImmutable('2026-10-14 09:00 UTC'));
        self::getContainer()->get(ConnectionRepository::class)->save($connection);
    }

    private function anExchangePlannedByTheProspectorOn(string $moment): string
    {
        $babbage = self::getContainer()->get(OrganizationRepository::class)->get(new OrganizationId($this->babbage()['organization']['id']));
        $planned = Exchange::plannedWithTheOrganization($babbage, new MemberId(DemoData::PROSPECTOR), new ExchangeId(self::newIdentity()), ExchangeFacts::planned(Channel::Call, new \DateTimeImmutable($moment), Duration::inMinutes(null), null), new \DateTimeImmutable('2026-10-13 09:00 UTC'));
        self::getContainer()->get(ExchangeRepository::class)->save($planned);

        return $planned->id()->value;
    }

    /**
     * @return array<string, mixed> the item of a frozen campaign of the account of the demonstration keeping the contact
     */
    private function frozenCampaignItem(string $id, string $contact): array
    {
        return ['PK' => 'ACCOUNT#'.DemoData::SOLEAN_IT, 'SK' => 'CAMPAIGN#'.$id, 'campaign' => [
            'id' => $id, 'accountId' => DemoData::SOLEAN_IT, 'createdBy' => self::newIdentity(), 'name' => 'Relance', 'channel' => 'email', 'tags' => ['ESN'],
            'message' => 'Bonjour', 'subject' => null, 'status' => 'frozen', 'recipients' => [['contactId' => $contact, 'status' => 'pending']],
            'createdAt' => '2026-10-14T09:00:00.000000Z', 'modifiedAt' => '2026-10-14T09:00:00.000000Z',
        ]];
    }

    /**
     * @param array<string, mixed> $recipient where the team was with them, as a campaign saved before keeping it said
     *
     * @return array<string, mixed> the item of a started campaign of the account of the demonstration keeping the
     *                              contact erased, without its name, still to handle unless said
     */
    private function startedCampaignItem(string $id, string $erased, array $recipient = ['status' => 'erased']): array
    {
        $item = $this->frozenCampaignItem($id, $erased);
        $item['campaign'] = [...$item['campaign'], 'status' => 'started', 'recipients' => [['contactId' => $erased, ...$recipient]], 'startedAt' => '2026-10-15T09:00:00.000000Z'];

        return $item;
    }

    /**
     * @return array<string, mixed> the item of a tagging of the account of the demonstration
     */
    private function taggingItem(string $kind, string $id): array
    {
        return ['PK' => 'ACCOUNT#'.DemoData::SOLEAN_IT, 'SK' => 'TAGGING#'.$kind.'#'.$id, 'tagging' => ['accountId' => DemoData::SOLEAN_IT, 'holder' => $kind, 'holderId' => $id, 'tags' => ['ESN'], 'createdAt' => '2026-10-14T09:00:00.000000Z', 'modifiedAt' => '2026-10-14T09:00:00.000000Z']];
    }

    /**
     * @param list<string> $contacts
     *
     * @return array<string, mixed> the item of the fact Campaigns keeps of an organization of the account of the demonstration
     */
    private function organizationFactItem(string $id, array $contacts, ?string $municipalityCode = self::COLMAR): array
    {
        return ['PK' => 'ACCOUNT#'.DemoData::SOLEAN_IT, 'SK' => 'FACT#ORGANIZATION#'.$id, 'revision' => 9, 'present' => true, 'contacts' => $contacts, 'municipalityCode' => $municipalityCode, 'country' => 'FR'];
    }

    private function rebuildTheFigures(): void
    {
        $tester = new CommandTester(new Application(self::$kernel)->find('app:reporting:rebuild'));
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();
    }

    private function rebuildTheTags(): void
    {
        $tester = new CommandTester(new Application(self::$kernel)->find('app:tags:rebuild'));
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
            'connectors' => $this->table('connectors'),
            'campaigns' => $this->campaignsTable(),
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

    private function campaignsTable(): Table
    {
        return $this->table('campaigns');
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
