<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Connectors\Domain\Exception\NoClient;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\ProviderClient;
use Prospecter\Core\Connectors\Domain\Repository\ProviderClientRepository;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

final readonly class InMemoryProviderClientRepository implements ProviderClientRepository
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function get(AccountId $account, Provider $provider): ProviderClient
    {
        return $this->find($account, $provider) ?? throw NoClient::at($provider);
    }

    public function find(AccountId $account, Provider $provider): ?ProviderClient
    {
        return $this->database->client($account->value, $provider->value);
    }

    public function save(ProviderClient $client): void
    {
        $this->database->storeClient($client);
    }

    public function remove(ProviderClient $client): void
    {
        $this->database->forgetClient($client);
    }
}
