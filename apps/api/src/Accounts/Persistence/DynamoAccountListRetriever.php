<?php

declare(strict_types=1);

namespace App\Accounts\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Accounts\Application\Port\AccountListRetriever;
use Prospecter\Core\SharedKernel\Domain\Search\Slice;

/**
 * Reads the accounts' ids in the `ACCOUNT#<id>` / `ACCOUNT` items that `DynamoAccountRepository` writes. `GSI1`
 * lists them under `ACCOUNTS`. It does not restore the accounts (ADR 0088).
 */
final readonly class DynamoAccountListRetriever implements AccountListRetriever
{
    public function __construct(private Table $accountsTable)
    {
    }

    public function sliceOf(?string $after, int $max): Slice
    {
        $items = $this->accountsTable->query(AccountsKeys::ALL_ACCOUNTS, Table::BY_GSI1, $after, $max + 1);

        return Documents::slice(array_map(static fn (array $item): string => Typed::string(Typed::document($item['account'])['id']), $items), $max, static fn (string $accountId): string => $accountId);
    }
}
