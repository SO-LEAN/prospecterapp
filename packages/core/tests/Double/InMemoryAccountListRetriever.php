<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Accounts\Application\Port\AccountListRetriever;
use Prospecter\Core\Accounts\Domain\Model\Account;
use Prospecter\Core\SharedKernel\Domain\SearchKeys;
use Prospecter\Core\SharedKernel\Domain\Slice;

/**
 * Reads the identities of the accounts the in-memory repository saved, as the adapter reads the items of the
 * table.
 */
final readonly class InMemoryAccountListRetriever implements AccountListRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function sliceOf(?string $after, int $max): Slice
    {
        $slice = NewestFirst::slice($this->database->accounts(), static fn (Account $account): string => (string) $account->id(), static fn (): SearchKeys => new SearchKeys(null, null), null, $after, $max);

        return new Slice(array_map(static fn (Account $account): string => (string) $account->id(), $slice->content), $slice->next);
    }
}
