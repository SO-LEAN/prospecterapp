<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Connectors\Application\AccountClient;
use Prospecter\Core\Connectors\Application\Port\AccountClientsRetriever;
use Prospecter\Core\Connectors\Domain\Model\ProviderClient;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Reads the clients the in-memory repository saved, as the adapter reads their items: without their secret.
 */
final readonly class InMemoryAccountClientsRetriever implements AccountClientsRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function of(AccountId $account): array
    {
        return array_map(self::withoutItsSecret(...), $this->database->clientsOf($account->value));
    }

    private static function withoutItsSecret(ProviderClient $client): AccountClient
    {
        $credentials = StateOf::the($client)['credentials'];

        return new AccountClient($credentials->provider->value, $credentials->id);
    }
}
