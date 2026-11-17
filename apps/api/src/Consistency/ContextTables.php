<?php

namespace App\Consistency;

use App\Accounts\Persistence\AccountsKeys;
use App\Campaigns\Persistence\CampaignsKeys;
use App\Connectors\Persistence\ConnectorsKeys;
use App\PersonalData\Persistence\PersonalDataKeys;
use App\Prospecting\Persistence\ExchangeKeys;
use App\Prospecting\Persistence\OrganizationKeys;
use App\Reporting\Persistence\ReportingKeys;
use App\Shared\Persistence\Table;

/**
 * The tables of every context, read directly by their keys and their indexes, never through the services the contexts
 * publish nor their retrievers: a check reading them through those would repeat their bugs without seeing them. The
 * only code reading the tables of several contexts (ADR 0105); it only reads.
 */
final readonly class ContextTables
{
    private const string CONNECTED = 'connected';
    private const string SYNCED = 'synced';

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
     * @return list<string> every account, found by its item, listed among the accounts or not
     */
    public function accounts(): array
    {
        return array_map(self::accountIdOf(...), $this->accountItems());
    }

    /**
     * @return list<string> the accounts whose item lacks the key listing it among the accounts
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
        return $item['account']['id'];
    }

    public function isAnAccount(string $accountId): bool
    {
        return null !== $this->accountsTable->item(AccountsKeys::account($accountId), AccountsKeys::ACCOUNT);
    }

    /**
     * @return list<array{id: string, accountId: string, personId: string}> the members of every account
     */
    public function members(): array
    {
        return array_map(static fn (array $item): array => $item['member'], $this->accountsTable->scan(AccountsKeys::MEMBER.'#'));
    }

    /**
     * @param list<string> $personIds
     *
     * @return list<string> those PersonalData keeps
     */
    public function keptPersonsAmong(array $personIds): array
    {
        $keys = array_map(static fn (string $id): array => [PersonalDataKeys::person($id), PersonalDataKeys::PERSON], $personIds);

        return array_map(static fn (array $item): string => $item['person']['id'], $this->personalDataTable->items($keys));
    }

    /**
     * @return list<string> the persons the account keeps for its prospecting
     */
    public function contactsOf(string $accountId): array
    {
        return array_column($this->contactDocumentsOf($accountId), 'id');
    }

    /**
     * @return list<array<string, mixed>> the documents of the persons the account keeps for its prospecting
     */
    public function contactDocumentsOf(string $accountId): array
    {
        return array_map(static fn (array $item): array => $item['person'], $this->personalDataTable->query(PersonalDataKeys::contactsOf($accountId), Table::BY_GSI1));
    }

    public function countContactsOf(string $accountId): int
    {
        return $this->personalDataTable->count(PersonalDataKeys::contactsOf($accountId), Table::BY_GSI1);
    }

    /**
     * @return list<array<string, mixed>> the documents of the organizations the account prospects
     */
    public function organizationsOf(string $accountId): array
    {
        return array_map(static fn (array $item): array => $item['organization'], $this->prospectingTable->query(OrganizationKeys::ofAccount($accountId), Table::BY_GSI1));
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
        return array_map(static fn (array $item): array => $item['tagging'], $this->campaignsTable->queryBetween(CampaignsKeys::account($accountId), null, ...CampaignsKeys::taggings()));
    }

    /**
     * @return array<string, list<string>> the contacts each campaign keeps among its recipients, by its id, none for a
     *                                     draft (ADR 0144)
     */
    public function recipientsOf(string $accountId): array
    {
        $campaigns = array_column($this->campaignsTable->queryBetween(CampaignsKeys::account($accountId), null, ...CampaignsKeys::campaigns()), 'campaign');

        return array_combine(array_column($campaigns, 'id'), array_map(static fn (array $campaign): array => array_column($campaign['recipients'] ?? [], 'contactId'), $campaigns));
    }

    /**
     * @return array<string, list<string>> the contacts each organization names, by its id, as the facts Campaigns keeps
     *                                     of those still present say (ADR 0141)
     */
    public function organizationFactsOf(string $accountId): array
    {
        $facts = array_filter($this->campaignsTable->queryBetween(CampaignsKeys::account($accountId), null, ...CampaignsKeys::organizationFacts()), static fn (array $item): bool => $item['present']);

        return array_combine(array_map(static fn (array $item): string => substr($item['SK'], strlen(CampaignsKeys::organizationFact(''))), $facts), array_column($facts, 'contacts'));
    }

    /**
     * @return array<string, array{hasEmail: bool, hasPhone: bool, hasMobile: bool, hasLinkedIn: bool}> how each contact
     *                                                                                                  is reached, by its id, as the facts Campaigns keeps of those still present say, their links left out
     */
    public function contactFactsOf(string $accountId): array
    {
        $reaches = [];

        foreach ($this->campaignsTable->queryBetween(CampaignsKeys::account($accountId), null, ...CampaignsKeys::contactFacts()) as $item) {
            if (array_key_exists('hasEmail', $item) && $item['present']) {
                $reaches[substr($item['SK'], strlen(CampaignsKeys::contactFact('')))] = ['hasEmail' => $item['hasEmail'], 'hasPhone' => $item['hasPhone'], 'hasMobile' => $item['hasMobile'], 'hasLinkedIn' => $item['hasLinkedIn']];
            }
        }

        return $reaches;
    }

    /**
     * @return list<array<string, mixed>> the documents of the exchanges the account keeps
     */
    public function exchangesOf(string $accountId): array
    {
        return array_map(static fn (array $item): array => $item['exchange'], $this->prospectingTable->query(ExchangeKeys::ofAccount($accountId), Table::BY_GSI2));
    }

    /**
     * @return list<array<string, mixed>> the documents of the exchanges whose item lacks the key listing it in its account
     */
    public function exchangesOutOfTheListOfTheirAccount(): array
    {
        $outOfTheList = array_filter($this->prospectingTable->scan(ExchangeKeys::exchange('')), static fn (array $item): bool => ExchangeKeys::ofAccount($item['exchange']['accountId']) !== ($item['GSI2PK'] ?? null));

        return array_values(array_map(static fn (array $item): array => $item['exchange'], $outOfTheList));
    }

    /**
     * @return array<string, array<string, string>> the provider each member is connected to, by member, by account;
     *                                              none to reconnect
     */
    public function connectedMembersByAccount(): array
    {
        $connected = [];
        foreach (array_column($this->connectorsTable->scan(ConnectorsKeys::member('')), 'connection') as $connection) {
            if (self::CONNECTED === $connection['state']) {
                $connected[$connection['accountId']][$connection['member']] = $connection['provider'];
            }
        }

        return $connected;
    }

    public function isSynced(string $exchangeId, string $provider): bool
    {
        $entry = $this->connectorsTable->item(ConnectorsKeys::exchange($exchangeId), ConnectorsKeys::entry($provider))['entry'] ?? null;

        return null !== $entry && self::SYNCED === ($entry['state'] ?? self::SYNCED);
    }

    /**
     * @return list<array<string, mixed>> the items of the figures of the account in the active version of Reporting, its
     *                                    `TOTAL` and its months; none before the first rebuild
     */
    public function figuresReportedFor(string $accountId): array
    {
        $active = $this->reportingTable->item(ReportingKeys::PROJECTION, ReportingKeys::ACTIVE);

        return null === $active ? [] : $this->reportingTable->query(ReportingKeys::figures((int) $active['version'], $accountId));
    }
}
