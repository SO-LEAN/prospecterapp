<?php

declare(strict_types=1);

namespace App\Connectors\Persistence;

use App\Shared\Persistence\Table;
use App\Shared\Typed;
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
            static fn (array $client): AccountClient => new AccountClient(Typed::string($client['provider']), Typed::string($client['clientId']), Typed::stringOrNull($client['mailboxTopic'] ?? null), Typed::stringOrNull($client['mailboxPushAccount'] ?? null)),
            Typed::documents(array_column($this->connectorsTable->query(ConnectorsKeys::account($account->value)), 'client')),
        );
    }
}
