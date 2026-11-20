<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Accounts\Domain\Model\Account;
use Prospecter\Core\Accounts\Domain\Model\Member;
use Prospecter\Core\Booking\Domain\Model\Appointment;
use Prospecter\Core\Booking\Domain\Model\BookingPage;
use Prospecter\Core\Campaigns\Domain\Model\Campaign;
use Prospecter\Core\Campaigns\Domain\Model\Fact as CampaignsFact;
use Prospecter\Core\Campaigns\Domain\Model\Holder;
use Prospecter\Core\Campaigns\Domain\Model\Tagging;
use Prospecter\Core\Connectors\Domain\Model\CalendarEntry;
use Prospecter\Core\Connectors\Domain\Model\Connection;
use Prospecter\Core\Connectors\Domain\Model\PendingAuthorization;
use Prospecter\Core\Connectors\Domain\Model\ProviderClient;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\Prospecting\Domain\Exception\ExchangeNotedMeanwhile;
use Prospecter\Core\Prospecting\Domain\Model\ContactToAttach;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFingerprint;
use Prospecter\Core\Prospecting\Domain\Model\Fingerprinted;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\ToDo;
use Prospecter\Core\Reporting\Domain\Model\Fact;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ReleasesEvents;

/**
 * The tables behind the in-memory repositories, with the constraints of the real ones: an email unique among
 * the persons of an account for a purpose, and among the organizations an account prospects. Like a database,
 * it keeps copies: a change is stored only when it is saved.
 */
final class InMemoryDatabase
{
    /** @var array<string, Account> */
    private array $accounts = [];
    /** @var array<string, Member> */
    private array $members = [];
    /** @var array<string, Person> */
    private array $persons = [];
    /** @var array<string, Organization> */
    private array $organizations = [];
    /** @var array<string, Exchange> */
    private array $exchanges = [];
    /** @var array<string, true> the exchanges of the e-mails imported, kept once erased, by their identity */
    private array $importedEmails = [];
    /** @var array<string, Fingerprinted> the exchanges noted under each fingerprint, kept once erased, by account and fingerprint */
    private array $fingerprints = [];
    /** @var array<string, ToDo> */
    private array $toDos = [];
    /** @var array<string, ContactToAttach> by their person */
    private array $contactsToAttach = [];
    /** @var array<string, int> the revision of each aggregate stored, by its identity, as the documents keep it */
    private array $revisions = [];
    /** @var array<int, array<string, Fact>> the facts each version of the figures keeps, by the aggregate they are about */
    private array $facts = [];
    /** @var array<int, array<string, array<string, int>>> the figures of each version, by account, then by name */
    private array $figures = [];
    private ?int $activeVersion = null;
    private ?int $versionBuilding = null;
    /** @var array<string, Connection> by the member and the provider */
    private array $connections = [];
    /** @var array<string, PendingAuthorization> by their state */
    private array $authorizations = [];
    /** @var array<string, ProviderClient> by the account and the provider */
    private array $clients = [];
    /** @var array<string, CalendarEntry> by the exchange pushed */
    private array $calendarEntries = [];
    /** @var array<string, Tagging> by the account and what it tags */
    private array $taggings = [];
    /** @var array<string, array<string, CampaignsFact>> the facts Campaigns keeps, by account, then by the aggregate they are about */
    private array $campaignsFacts = [];
    /** @var array<string, Campaign> by the account and its identity */
    private array $campaigns = [];
    /** @var array<string, BookingPage> by their host */
    private array $bookingPages = [];
    /** @var array<string, Appointment> by their identity */
    private array $appointments = [];

    public function isEmpty(): bool
    {
        return [] === [...$this->accounts, ...$this->members, ...$this->persons, ...$this->organizations, ...$this->exchanges, ...$this->toDos];
    }

    public function snapshot(): self
    {
        return clone $this;
    }

    /**
     * The stored copies are never changed in place: the arrays of a snapshot keep the state it was taken in.
     */
    public function restore(self $snapshot): void
    {
        $this->accounts = $snapshot->accounts;
        $this->members = $snapshot->members;
        $this->persons = $snapshot->persons;
        $this->organizations = $snapshot->organizations;
        $this->exchanges = $snapshot->exchanges;
        $this->importedEmails = $snapshot->importedEmails;
        $this->fingerprints = $snapshot->fingerprints;
        $this->toDos = $snapshot->toDos;
        $this->contactsToAttach = $snapshot->contactsToAttach;
        $this->facts = $snapshot->facts;
        $this->figures = $snapshot->figures;
        $this->activeVersion = $snapshot->activeVersion;
        $this->versionBuilding = $snapshot->versionBuilding;
        $this->revisions = $snapshot->revisions;
        $this->connections = $snapshot->connections;
        $this->authorizations = $snapshot->authorizations;
        $this->clients = $snapshot->clients;
        $this->calendarEntries = $snapshot->calendarEntries;
        $this->taggings = $snapshot->taggings;
        $this->campaignsFacts = $snapshot->campaignsFacts;
        $this->campaigns = $snapshot->campaigns;
        $this->bookingPages = $snapshot->bookingPages;
        $this->appointments = $snapshot->appointments;
    }

    public function storeBookingPage(BookingPage $page): void
    {
        $this->bookingPages[$page->host()->value] = self::withoutItsEvents($page);
    }

    public function bookingPage(string $host): ?BookingPage
    {
        $page = $this->bookingPages[$host] ?? null;

        return null === $page ? null : clone $page;
    }

    /**
     * As the table finds a page by the link of its code: only the code a page has now leads to it.
     */
    public function bookingPageAt(string $code): ?BookingPage
    {
        $page = array_find($this->bookingPages, static fn (BookingPage $page): bool => $page->code()?->value === $code);

        return null === $page ? null : clone $page;
    }

    public function storeAppointment(Appointment $appointment): void
    {
        $this->appointments[$appointment->id()->value] = self::withoutItsEvents($appointment);
    }

    public function appointment(string $id): ?Appointment
    {
        return $this->appointments[$id] ?? null;
    }

    /**
     * @return list<Appointment>
     */
    public function appointments(): array
    {
        return array_values($this->appointments);
    }

    public function campaignsFact(string $account, string $aggregate): ?CampaignsFact
    {
        return $this->campaignsFacts[$account][$aggregate] ?? null;
    }

    public function storeCampaignsFact(string $account, string $aggregate, CampaignsFact $fact): void
    {
        $this->campaignsFacts[$account][$aggregate] = $fact;
    }

    /**
     * @return list<CampaignsFact>
     */
    public function campaignsFactsOf(string $account): array
    {
        return array_values($this->campaignsFacts[$account] ?? []);
    }

    public function loseCampaignsFactsOf(string $account): void
    {
        unset($this->campaignsFacts[$account]);
    }

    public function storeTagging(Tagging $tagging): void
    {
        $this->taggings[self::taggingKey($tagging->accountId()->value, $tagging->holder())] = self::withoutItsEvents($tagging);
    }

    public function forgetTagging(Tagging $tagging): void
    {
        unset($this->taggings[self::taggingKey($tagging->accountId()->value, $tagging->holder())]);
    }

    public function tagging(string $account, Holder $holder): ?Tagging
    {
        $tagging = $this->taggings[self::taggingKey($account, $holder)] ?? null;

        return null === $tagging ? null : clone $tagging;
    }

    /**
     * @return list<Tagging>
     */
    public function taggingsOf(string $account): array
    {
        return array_values(array_map(
            static fn (Tagging $tagging): Tagging => clone $tagging,
            array_filter($this->taggings, static fn (Tagging $tagging): bool => $tagging->accountId()->value === $account),
        ));
    }

    public function storeCampaign(Campaign $campaign): void
    {
        $this->campaigns[self::campaignKey($campaign->accountId()->value, $campaign->id()->value)] = self::withoutItsEvents($campaign);
    }

    public function forgetCampaign(Campaign $campaign): void
    {
        unset($this->campaigns[self::campaignKey($campaign->accountId()->value, $campaign->id()->value)]);
    }

    public function campaign(string $account, string $id): ?Campaign
    {
        $campaign = $this->campaigns[self::campaignKey($account, $id)] ?? null;

        return null === $campaign ? null : clone $campaign;
    }

    /**
     * @return list<Campaign>
     */
    public function campaignsOf(string $account): array
    {
        return array_values(array_map(
            static fn (Campaign $campaign): Campaign => clone $campaign,
            array_filter($this->campaigns, static fn (Campaign $campaign): bool => $campaign->accountId()->value === $account),
        ));
    }

    private static function campaignKey(string $account, string $id): string
    {
        return $account.'#'.$id;
    }

    private static function taggingKey(string $account, Holder $holder): string
    {
        return $account.'#'.$holder->kind->value.'#'.$holder->id->value;
    }

    public function storeCalendarEntry(CalendarEntry $entry): void
    {
        $this->calendarEntries[$entry->exchange()->value] = clone $entry;
    }

    public function forgetCalendarEntry(CalendarEntry $entry): void
    {
        unset($this->calendarEntries[$entry->exchange()->value]);
    }

    /**
     * @return list<CalendarEntry>
     */
    public function calendarEntriesOf(string $contact): array
    {
        return array_values(array_map(
            static fn (CalendarEntry $entry): CalendarEntry => clone $entry,
            array_filter($this->calendarEntries, static fn (CalendarEntry $entry): bool => $entry->contact()?->value === $contact),
        ));
    }

    /**
     * @return list<CalendarEntry>
     */
    public function calendarEntriesOfMember(string $member): array
    {
        return array_values(array_map(
            static fn (CalendarEntry $entry): CalendarEntry => clone $entry,
            array_filter($this->calendarEntries, static fn (CalendarEntry $entry): bool => $entry->member()->value === $member),
        ));
    }

    public function calendarEntry(string $exchange): ?CalendarEntry
    {
        $entry = $this->calendarEntries[$exchange] ?? null;

        return null === $entry ? null : clone $entry;
    }

    public function storeClient(ProviderClient $client): void
    {
        $this->clients[self::clientKey($client->accountId()->value, $client->provider()->value)] = self::withoutItsEvents($client);
    }

    public function forgetClient(ProviderClient $client): void
    {
        unset($this->clients[self::clientKey($client->accountId()->value, $client->provider()->value)]);
    }

    public function client(string $account, string $provider): ?ProviderClient
    {
        $client = $this->clients[self::clientKey($account, $provider)] ?? null;

        return null === $client ? null : clone $client;
    }

    /**
     * @return list<ProviderClient>
     */
    public function clientsOf(string $account): array
    {
        return array_values(array_map(
            static fn (ProviderClient $client): ProviderClient => clone $client,
            array_filter($this->clients, static fn (ProviderClient $client): bool => $client->accountId()->value === $account),
        ));
    }

    private static function clientKey(string $account, string $provider): string
    {
        return $account.'#'.$provider;
    }

    public function storeConnection(Connection $connection): void
    {
        $this->connections[self::connectionKey($connection->member()->value, $connection->provider()->value)] = self::withoutItsEvents($connection);
    }

    public function forgetConnection(Connection $connection): void
    {
        unset($this->connections[self::connectionKey($connection->member()->value, $connection->provider()->value)]);
    }

    public function connection(string $member, string $provider): ?Connection
    {
        $connection = $this->connections[self::connectionKey($member, $provider)] ?? null;

        return null === $connection ? null : clone $connection;
    }

    /**
     * @return list<Connection>
     */
    public function connections(): array
    {
        return array_values(array_map(static fn (Connection $connection): Connection => clone $connection, $this->connections));
    }

    /**
     * @return list<Connection>
     */
    public function connectionsOf(string $member): array
    {
        return array_values(array_map(
            static fn (Connection $connection): Connection => clone $connection,
            array_filter($this->connections, static fn (Connection $connection): bool => $connection->member()->value === $member),
        ));
    }

    private static function connectionKey(string $member, string $provider): string
    {
        return $member.'#'.$provider;
    }

    public function keepAuthorization(PendingAuthorization $authorization): void
    {
        $this->authorizations[$authorization->state] = $authorization;
    }

    public function forgetAuthorization(string $state): void
    {
        unset($this->authorizations[$state]);
    }

    public function authorization(string $state): ?PendingAuthorization
    {
        return $this->authorizations[$state] ?? null;
    }

    public function storeExchange(Exchange $exchange): void
    {
        $this->registerFingerprintOf($exchange);
        $this->exchanges[(string) $exchange->id()] = self::withoutItsEvents($exchange);
        if (null !== StateOf::the($exchange)['importedFrom']) {
            $this->importedEmails[(string) $exchange->id()] = true;
        }
        $this->revise((string) $exchange->id());
    }

    public function forgetExchange(string $id): void
    {
        unset($this->exchanges[$id], $this->revisions[$id]);
    }

    public function exchange(string $id): ?Exchange
    {
        return isset($this->exchanges[$id]) ? clone $this->exchanges[$id] : null;
    }

    public function fingerprinted(AccountId $accountId, ExchangeFingerprint $fingerprint): ?Fingerprinted
    {
        return $this->fingerprints[self::fingerprintKey($accountId, $fingerprint)] ?? null;
    }

    /**
     * Under the condition the adapter writes it: a fingerprint registered for another exchange is taken.
     *
     * @throws ExchangeNotedMeanwhile
     */
    private function registerFingerprintOf(Exchange $exchange): void
    {
        $fingerprint = $exchange->fingerprint();
        if (null === $fingerprint) {
            return;
        }
        $key = self::fingerprintKey($exchange->accountId(), $fingerprint);
        $registered = $this->fingerprints[$key] ?? null;
        if (null !== $registered && !$registered->exchange->equals($exchange->id())) {
            throw ExchangeNotedMeanwhile::byAnother();
        }
        $this->fingerprints[$key] = $registered ?? new Fingerprinted($exchange->id());
    }

    /**
     * Its fingerprint outlives it, with who erased it.
     */
    public function eraseFingerprintOf(Exchange $exchange): void
    {
        $fingerprint = $exchange->fingerprint();
        if (null !== $fingerprint) {
            $this->fingerprints[self::fingerprintKey($exchange->accountId(), $fingerprint)] = new Fingerprinted($exchange->id(), StateOf::the($exchange)['erasure']);
        }
    }

    private static function fingerprintKey(AccountId $accountId, ExchangeFingerprint $fingerprint): string
    {
        return $accountId->value.'#'.$fingerprint->value;
    }

    public function wasImported(string $id): bool
    {
        return isset($this->importedEmails[$id]);
    }

    /**
     * @return list<Exchange>
     */
    public function exchanges(): array
    {
        return array_values(array_map(static fn (Exchange $exchange): Exchange => clone $exchange, $this->exchanges));
    }

    public function storeToDo(ToDo $toDo): void
    {
        $this->toDos[(string) $toDo->id()] = self::withoutItsEvents($toDo);
    }

    public function forgetToDo(string $id): void
    {
        unset($this->toDos[$id]);
    }

    public function toDo(string $id): ?ToDo
    {
        return isset($this->toDos[$id]) ? clone $this->toDos[$id] : null;
    }

    /**
     * @return list<ToDo>
     */
    public function toDos(): array
    {
        return array_values(array_map(static fn (ToDo $toDo): ToDo => clone $toDo, $this->toDos));
    }

    public function storeContactToAttach(ContactToAttach $contactToAttach): void
    {
        $this->contactsToAttach[(string) $contactToAttach->id()] = self::withoutItsEvents($contactToAttach);
    }

    public function forgetContactToAttach(string $person): void
    {
        unset($this->contactsToAttach[$person]);
    }

    public function contactToAttach(string $person): ?ContactToAttach
    {
        return isset($this->contactsToAttach[$person]) ? clone $this->contactsToAttach[$person] : null;
    }

    /**
     * @return list<ContactToAttach>
     */
    public function contactsToAttach(): array
    {
        return array_values(array_map(static fn (ContactToAttach $contactToAttach): ContactToAttach => clone $contactToAttach, $this->contactsToAttach));
    }

    public function storeAccount(Account $account): void
    {
        $this->accounts[(string) $account->id()] = self::withoutItsEvents($account);
    }

    public function storeMember(Member $member): void
    {
        $this->members[(string) $member->id()] = clone $member;
    }

    public function storePerson(Person $person): void
    {
        $this->persons[(string) $person->id()] = self::withoutItsEvents($person);
        $this->revise((string) $person->id());
    }

    public function forgetPerson(string $id): void
    {
        unset($this->persons[$id], $this->revisions[$id]);
    }

    public function forgetOrganization(string $id): void
    {
        unset($this->organizations[$id], $this->revisions[$id]);
    }

    public function storeOrganization(Organization $organization): void
    {
        $this->organizations[(string) $organization->id()] = self::withoutItsEvents($organization);
        $this->revise((string) $organization->id());
    }

    /**
     * How many times the organization, the exchange or the person was saved: the adapters count it in its document, the
     * aggregate does not know it.
     */
    public function revisionOf(string $id): int
    {
        return $this->revisions[$id] ?? 0;
    }

    private function revise(string $id): void
    {
        $this->revisions[$id] = $this->revisionOf($id) + 1;
    }

    public function account(string $id): ?Account
    {
        return isset($this->accounts[$id]) ? clone $this->accounts[$id] : null;
    }

    public function member(string $id): ?Member
    {
        return isset($this->members[$id]) ? clone $this->members[$id] : null;
    }

    public function person(string $id): ?Person
    {
        return isset($this->persons[$id]) ? clone $this->persons[$id] : null;
    }

    public function organization(string $id): ?Organization
    {
        return isset($this->organizations[$id]) ? clone $this->organizations[$id] : null;
    }

    /**
     * @return list<Account>
     */
    public function accounts(): array
    {
        return array_values(array_map(static fn (Account $account): Account => clone $account, $this->accounts));
    }

    /**
     * @return list<Member>
     */
    public function members(): array
    {
        return array_values(array_map(static fn (Member $member): Member => clone $member, $this->members));
    }

    /**
     * @return list<Person>
     */
    public function persons(): array
    {
        return array_values(array_map(static fn (Person $person): Person => clone $person, $this->persons));
    }

    /**
     * @return list<Organization>
     */
    public function organizations(): array
    {
        return array_values(array_map(static fn (Organization $organization): Organization => clone $organization, $this->organizations));
    }

    public function fact(int $version, string $aggregate): ?Fact
    {
        return $this->facts[$version][$aggregate] ?? null;
    }

    public function storeFact(int $version, string $aggregate, Fact $fact): void
    {
        $this->facts[$version][$aggregate] = $fact;
    }

    public function figure(int $version, string $accountId, string $name): int
    {
        return $this->figures[$version][$accountId][$name] ?? 0;
    }

    public function addToFigure(int $version, string $accountId, string $name, int $change): void
    {
        $this->figures[$version][$accountId][$name] = $this->figure($version, $accountId, $name) + $change;
    }

    public function activeVersion(): ?int
    {
        return $this->activeVersion;
    }

    public function versionBuilding(): ?int
    {
        return $this->versionBuilding;
    }

    public function startBuilding(int $version): void
    {
        $this->versionBuilding = $version;
    }

    public function abandonBuilding(): void
    {
        $this->versionBuilding = null;
    }

    /**
     * The keys of the facts (version, aggregate) and of the figures (version, account, name) of every version but the
     * one kept, as a scan reads them.
     *
     * @return array{facts: list<array{int, string}>, figures: list<array{int, string, string}>}
     */
    public function itemsOfEveryVersionBut(?int $kept): array
    {
        $items = ['facts' => [], 'figures' => []];

        foreach ($this->facts as $version => $facts) {
            foreach ($version === $kept ? [] : array_keys($facts) as $aggregate) {
                $items['facts'][] = [$version, $aggregate];
            }
        }

        foreach ($this->figures as $version => $accounts) {
            foreach ($version === $kept ? [] : $accounts as $account => $figures) {
                foreach (array_keys($figures) as $name) {
                    $items['figures'][] = [$version, $account, $name];
                }
            }
        }

        return $items;
    }

    /**
     * @param array{facts: list<array{int, string}>, figures: list<array{int, string, string}>} $items
     */
    public function removeItems(array $items): void
    {
        foreach ($items['facts'] as [$version, $aggregate]) {
            unset($this->facts[$version][$aggregate]);
        }

        foreach ($items['figures'] as [$version, $account, $name]) {
            unset($this->figures[$version][$account][$name]);
        }
    }

    /**
     * @return list<int> the versions keeping a fact or a figure
     */
    public function versionsKept(): array
    {
        $items = $this->itemsOfEveryVersionBut(null);
        $versions = array_unique(array_map(static fn (array $item): int => $item[0], [...$items['facts'], ...$items['figures']]));
        sort($versions);

        return $versions;
    }

    /**
     * The version built becomes the active one, the former one removed with its facts and its figures.
     */
    public function activate(int $version): void
    {
        if (null !== $this->activeVersion) {
            unset($this->facts[$this->activeVersion], $this->figures[$this->activeVersion]);
        }

        [$this->activeVersion, $this->versionBuilding] = [$version, null];
    }

    /**
     * Like the DynamoDB adapters, a stored aggregate keeps its state, not the events it recorded: they belong to
     * the use case that changed it.
     *
     * @template T of ReleasesEvents
     *
     * @param T $aggregate
     *
     * @return T
     */
    private static function withoutItsEvents(ReleasesEvents $aggregate): ReleasesEvents
    {
        $stored = clone $aggregate;
        $stored->releaseEvents();

        return $stored;
    }
}
