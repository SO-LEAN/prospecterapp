<?php

declare(strict_types=1);

namespace App\Connectors\Persistence;

use App\Shared\Persistence\Table;
use Prospecter\Core\Connectors\Application\AccountClient;
use Prospecter\Core\Connectors\Application\Port\AccountClientsRetriever;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * The clients of an account read from their items alone, without their secrets.
 */
final readonly class DynamoAccountClientsRetriever implements AccountClientsRetriever
{
    public function __construct(private Table $connectorsTable)
    {
    }

    public function of(AccountId $account): array
    {
        return array_map(
            static fn (array $item): AccountClient => new AccountClient($item['client']['provider'], $item['client']['clientId'], $item['client']['mailboxTopic'] ?? null, $item['client']['mailboxPushAccount'] ?? null),
            $this->connectorsTable->query(ConnectorsKeys::account($account->value)),
        );
    }
}
