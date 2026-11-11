<?php

namespace App\Consistency;

use App\Accounts\Persistence\AccountsKeys;
use App\PersonalData\Persistence\PersonalDataKeys;
use App\Prospecting\Persistence\ExchangeKeys;
use App\Prospecting\Persistence\OrganizationKeys;
use App\Reporting\Persistence\ReportingKeys;
use App\Shared\Persistence\Table;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * The tables of every context, read directly by their keys and their indexes, never through the services the contexts
 * publish nor their retrievers: a check reading them through those would repeat their bugs without seeing them. The
 * only code reading the tables of several contexts (ADR 0105); it only reads.
 */
final readonly class ContextTables
{
    public function __construct(
        private Table $accountsTable,
        private Table $personalDataTable,
        private Table $prospectingTable,
        private Table $reportingTable,
    ) {
    }

    /**
     * @return list<string>
     */
    public function accounts(): array
    {
        return array_map(static fn (array $item): string => $item['account']['id'], $this->accountsTable->query(AccountsKeys::ALL_ACCOUNTS, Table::BY_GSI1));
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
        return array_map(static fn (array $item): string => $item['person']['id'], $this->personalDataTable->query(self::contactsListOf($accountId), Table::BY_GSI1));
    }

    public function countContactsOf(string $accountId): int
    {
        return $this->personalDataTable->count(self::contactsListOf($accountId), Table::BY_GSI1);
    }

    private static function contactsListOf(string $accountId): string
    {
        return PersonalDataKeys::listOf(new AccountId($accountId), Purpose::Prospecting);
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
     * @return list<array<string, mixed>> the documents of the exchanges the account keeps
     */
    public function exchangesOf(string $accountId): array
    {
        return array_map(static fn (array $item): array => $item['exchange'], $this->prospectingTable->query(ExchangeKeys::ofAccount($accountId), Table::BY_GSI2));
    }

    public function countExchangesOf(string $accountId): int
    {
        return $this->prospectingTable->count(ExchangeKeys::ofAccount($accountId), Table::BY_GSI2);
    }

    /**
     * @return list<array<string, mixed>> the items of the figures of the account in the active version of Reporting, its
     *                                    `TOTAL` and its months; none before the first rebuild
     */
    public function figuresReportedFor(string $accountId): array
    {
        $active = $this->reportingTable->item(ReportingKeys::PROJECTION, ReportingKeys::ACTIVE);

        return null === $active ? [] : $this->reportingTable->query(ReportingKeys::figures((int) $active['version'], new AccountId($accountId)));
    }
}
