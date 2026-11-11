<?php

namespace App\Accounts\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use Prospecter\Core\Accounts\Application\Port\AccountListRetriever;
use Prospecter\Core\SharedKernel\Domain\Slice;

/**
 * Reads the identities of the accounts in the items `ACCOUNT#<id>` / `ACCOUNT` that `DynamoAccountRepository`
 * writes, listed in `GSI1` (`ACCOUNTS`), without restoring them (ADR 0088).
 */
final readonly class DynamoAccountListRetriever implements AccountListRetriever
{
    public function __construct(private Table $accountsTable)
    {
    }

    public function sliceOf(?string $after, int $max): Slice
    {
        $items = $this->accountsTable->query(AccountsKeys::ALL_ACCOUNTS, Table::BY_GSI1, $after, $max + 1);

        return Documents::slice(array_map(static fn (array $item): string => $item['account']['id'], $items), $max, static fn (string $accountId): string => $accountId);
    }
}
