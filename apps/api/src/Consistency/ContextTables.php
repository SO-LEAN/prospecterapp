<?php

declare(strict_types=1);

namespace App\Consistency;

use App\Accounts\Persistence\AccountsKeys;
use App\Campaigns\Persistence\CampaignsKeys;
use App\Campaigns\Persistence\OrganizationFactItem;
use App\Connectors\Persistence\ConnectorsKeys;
use App\PersonalData\Persistence\PersonalDataKeys;
use App\Prospecting\Persistence\DynamoContactToAttachRepository;
use App\Prospecting\Persistence\ExchangeKeys;
use App\Prospecting\Persistence\OrganizationKeys;
use App\Reporting\Persistence\ReportingKeys;
use App\Shared\Persistence\Table;
use App\Shared\Typed;

/**
 * Reads every context's tables directly, by their keys and their indexes. It never goes through the services that the
 * contexts publish, nor through their retrievers: a check that read through those would repeat their bugs without
 * seeing them. It is the only code that reads several contexts' tables (ADR 0105), and it only reads.
 */
final readonly class ContextTables
{
    private const string CONNECTED = 'connected';
    private const string SYNCED = 'synced';
    private const string ERASED = 'erased';

    public function __construct(
        private Table $accountsTable,
        private Table $campaignsTable,
        private Table $connectorsTable,
        private Table $personalDataTable,
        private Table $prospectingTable,
        private Table $reportingTable,
    ) {
    }

    /**
     * @return list<string> every account, found by its item, whether it is listed among the accounts or not
     */
    public function accounts(): array
    {
        return array_map(self::accountIdOf(...), $this->accountItems());
    }

    /**
     * @return list<string> the accounts whose item lacks the key that lists it among the accounts
     */
    public function accountsOutOfTheirList(): array
    {
        $outOfTheList = array_filter($this->accountItems(), static fn (array $item): bool => AccountsKeys::ALL_ACCOUNTS !== ($item['GSI1PK'] ?? null));

        return array_values(array_map(self::accountIdOf(...), $outOfTheList));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function accountItems(): array
    {
        return $this->accountsTable->scan(AccountsKeys::account(''));
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function accountIdOf(array $item): string
    {
        return Typed::string(Typed::document($item['account'])['id']);
    }

    public function isAnAccount(string $accountId): bool
    {
        return null !== $this->accountsTable->item(AccountsKeys::account($accountId), AccountsKeys::ACCOUNT);
    }

    /**
     * @return list<array{id: string, accountId: string, personId: string}> every account's members
     */
    public function members(): array
    {
        return array_map(static fn (array $item): array => self::memberOf(Typed::document($item['member'])), $this->accountsTable->scan(AccountsKeys::MEMBER.'#'));
    }

    /**
     * @param array<string, mixed> $member
     *
     * @return array{id: string, accountId: string, personId: string}
     */
    private static function memberOf(array $member): array
    {
        return ['id' => Typed::string($member['id']), 'accountId' => Typed::string($member['accountId']), 'personId' => Typed::string($member['personId'])];
    }

    /**
     * @param list<string> $personIds
     *
     * @return list<string> those that PersonalData keeps
     */
    public function keptPersonsAmong(array $personIds): array
    {
        $keys = array_map(static fn (string $id): array => [PersonalDataKeys::person($id), PersonalDataKeys::PERSON], $personIds);

        return array_map(static fn (array $item): string => Typed::string(Typed::document($item['person'])['id']), $this->personalDataTable->items($keys));
    }

    /**
     * @return list<string> the persons the account keeps for its prospecting
     */
    public function contactsOf(string $accountId): array
    {
        return Typed::strings(array_column($this->contactDocumentsOf($accountId), 'id'));
    }

    /**
     * @return list<array<string, mixed>> the documents of the persons that the account keeps for its prospecting
     */
    public function contactDocumentsOf(string $accountId): array
    {
        return array_map(static fn (array $item): array => Typed::document($item['person']), $this->personalDataTable->query(PersonalDataKeys::contactsOf($accountId), Table::BY_GSI1));
    }

    public function countContactsOf(string $accountId): int
    {
        return $this->personalDataTable->count(PersonalDataKeys::contactsOf($accountId), Table::BY_GSI1);
    }

    /**
     * @return list<array<string, mixed>> the documents of the organizations that the account prospects
     */
    public function organizationsOf(string $accountId): array
    {
        return array_map(static fn (array $item): array => Typed::document($item['organization']), $this->prospectingTable->query(OrganizationKeys::ofAccount($accountId), Table::BY_GSI1));
    }

    public function countOrganizationsOf(string $accountId): int
    {
        return $this->prospectingTable->count(OrganizationKeys::ofAccount($accountId), Table::BY_GSI1);
    }

    /**
     * @return list<array{holder: string, holderId: string}> the organizations and the contacts the account tagged
     */
    public function taggingsOf(string $accountId): array
    {
        return array_map(static fn (array $item): array => self::taggingOf(Typed::document($item['tagging'])), $this->campaignsTable->queryBetween(CampaignsKeys::account($accountId), null, ...CampaignsKeys::taggings()));
    }

    /**
     * @param array<string, mixed> $tagging
     *
     * @return array{holder: string, holderId: string}
     */
    private static function taggingOf(array $tagging): array
    {
        return ['holder' => Typed::string($tagging['holder']), 'holderId' => Typed::string($tagging['holderId'])];
    }

    /**
     * @return array<string, list<string>> each campaign's recipient contacts, by the campaign's id, none for a
     *                                     draft (ADR 0144), without the erased ones a started campaign keeps
     */
    public function recipientsOf(string $accountId): array
    {
        $campaigns = Typed::documents(array_column($this->campaignsTable->queryBetween(CampaignsKeys::account($accountId), null, ...CampaignsKeys::campaigns()), 'campaign'));

        return array_combine(Typed::strings(array_column($campaigns, 'id')), array_map(self::notErased(...), $campaigns));
    }

    /**
     * @param array<string, mixed> $campaign
     *
     * @return list<string>
     */
    private static function notErased(array $campaign): array
    {
        $kept = array_filter(Typed::documents($campaign['recipients'] ?? []), static fn (array $recipient): bool => !self::isErased($recipient));

        return Typed::strings(array_column($kept, 'contactId'));
    }

    /**
     * A recipient saved before erased recipients kept what was done with them has no `erased` flag: its status alone
     * tells.
     *
     * @param array<string, mixed> $recipient
     */
    private static function isErased(array $recipient): bool
    {
        return isset($recipient['erased']) ? Typed::bool($recipient['erased']) : self::ERASED === $recipient['status'];
    }

    /**
     * @return array<string, list<string>> the contacts each organization names, by its id, as Campaigns' facts about the
     *                                     organizations still present say (ADR 0141)
     */
    public function organizationFactsOf(string $accountId): array
    {
        $facts = $this->presentOrganizationFactsOf($accountId);

        return array_combine(array_map(self::organizationOfTheFact(...), $facts), array_map(static fn (array $item): array => Typed::strings($item['contacts']), $facts));
    }

    /**
     * @return array<string, array{municipalityCode: ?string, country: ?string}> where each organization is, by its id, as
     *                                                                           Campaigns' facts about the organizations still present say (ADR 0142)
     */
    public function organizationLocalitiesOf(string $accountId): array
    {
        $facts = $this->presentOrganizationFactsOf($accountId);

        return array_combine(array_map(self::organizationOfTheFact(...), $facts), array_map(OrganizationFactItem::localityIn(...), $facts));
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function organizationOfTheFact(array $item): string
    {
        return CampaignsKeys::organizationOfFact(Typed::string($item['SK']));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function presentOrganizationFactsOf(string $accountId): array
    {
        return array_values(array_filter($this->campaignsTable->queryBetween(CampaignsKeys::account($accountId), null, ...CampaignsKeys::organizationFacts()), static fn (array $item): bool => Typed::bool($item['present'])));
    }

    /**
     * @return array<string, array{hasEmail: bool, hasPhone: bool, hasMobile: bool, hasLinkedIn: bool}> how each contact
     *                                                                                                  is reached, by its id, as Campaigns' facts about the contacts still present say, without their links
     */
    public function contactFactsOf(string $accountId): array
    {
        $reaches = [];

        foreach ($this->campaignsTable->queryBetween(CampaignsKeys::account($accountId), null, ...CampaignsKeys::contactFacts()) as $item) {
            if (array_key_exists('hasEmail', $item) && Typed::bool($item['present'])) {
                $reaches[substr(Typed::string($item['SK']), strlen(CampaignsKeys::contactFact('')))] = ['hasEmail' => Typed::bool($item['hasEmail']), 'hasPhone' => Typed::bool($item['hasPhone']), 'hasMobile' => Typed::bool($item['hasMobile']), 'hasLinkedIn' => Typed::bool($item['hasLinkedIn'])];
            }
        }

        return $reaches;
    }

    /**
     * The account's contacts to attach, as their items keep them. They are read in the partition that lists every
     * account's contacts by the moment of their erasure (ADR 0146, 0147).
     *
     * @return list<array<string, mixed>>
     */
    public function contactsToAttachOf(string $accountId): array
    {
        $items = $this->prospectingTable->queryAfter(DynamoContactToAttachRepository::ERASURE, Table::BY_GSI2, '0');
        $documents = array_map(static fn (array $item): array => Typed::document($item['contactToAttach']), $items);

        return array_values(array_filter($documents, static fn (array $document): bool => $document['accountId'] === $accountId));
    }

    /**
     * @return list<array<string, mixed>> the documents of the exchanges that the account keeps
     */
    public function exchangesOf(string $accountId): array
    {
        return array_map(static fn (array $item): array => Typed::document($item['exchange']), $this->prospectingTable->query(ExchangeKeys::ofAccount($accountId), Table::BY_GSI2));
    }

    /**
     * @return list<array<string, mixed>> the documents of the exchanges whose item lacks the key that lists it in its account
     */
    public function exchangesOutOfTheListOfTheirAccount(): array
    {
        $exchanges = array_map(static fn (array $item): array => ['document' => Typed::document($item['exchange']), 'listedIn' => $item['GSI2PK'] ?? null], $this->prospectingTable->scan(ExchangeKeys::exchange('')));
        $outOfTheList = array_filter($exchanges, static fn (array $exchange): bool => ExchangeKeys::ofAccount(Typed::string($exchange['document']['accountId'])) !== $exchange['listedIn']);

        return array_column($outOfTheList, 'document');
    }

    /**
     * @return array<string, array<string, string>> the provider each member is connected to, by member, by account,
     *                                              except those to reconnect
     */
    public function connectedMembersByAccount(): array
    {
        $connected = [];
        foreach (Typed::documents(array_column($this->connectorsTable->scan(ConnectorsKeys::member('')), 'connection')) as $connection) {
            if (self::CONNECTED === $connection['state']) {
                $connected[Typed::string($connection['accountId'])][Typed::string($connection['member'])] = Typed::string($connection['provider']);
            }
        }

        return $connected;
    }

    public function isSynced(string $exchangeId, string $provider): bool
    {
        $entry = $this->connectorsTable->item(ConnectorsKeys::exchange($exchangeId), ConnectorsKeys::entry($provider))['entry'] ?? null;

        return null !== $entry && self::SYNCED === (Typed::document($entry)['state'] ?? self::SYNCED);
    }

    /**
     * @return list<array<string, mixed>> the account's figure items in Reporting's active version, its
     *                                    `TOTAL` and its months, none before the first rebuild
     */
    public function figuresReportedFor(string $accountId): array
    {
        $active = $this->reportingTable->item(ReportingKeys::PROJECTION, ReportingKeys::ACTIVE);

        return null === $active ? [] : $this->reportingTable->query(ReportingKeys::figures(Typed::int($active['version']), $accountId));
    }
}
