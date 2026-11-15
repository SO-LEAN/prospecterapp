<?php

declare(strict_types=1);

namespace App\Connectors\Persistence;

use App\Shared\Persistence\AccountSecrets;
use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use App\Shared\Persistence\Table;
use Prospecter\Core\Connectors\Domain\Exception\NoClient;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\ProviderClient;
use Prospecter\Core\Connectors\Domain\Repository\ProviderClientRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * The client of an account at a provider (ADR 0125): its id in the table of Connectors with the version of its
 * secret, the secret a parameter of SSM; a client whose secret is gone is no client.
 */
final readonly class DynamoProviderClientRepository implements ProviderClientRepository
{
    public function __construct(private Table $connectorsTable, private AccountSecrets $accountSecrets)
    {
    }

    public function get(AccountId $account, Provider $provider): ProviderClient
    {
        return $this->find($account, $provider) ?? throw NoClient::at($provider);
    }

    public function find(AccountId $account, Provider $provider): ?ProviderClient
    {
        $document = $this->connectorsTable->item(ConnectorsKeys::account($account->value), ConnectorsKeys::client($provider->value))['client'] ?? null;
        $secret = null === $document ? null : $this->accountSecrets->find($account->value, self::secretOf($provider), (int) $document['secretVersion']);

        return null === $secret ? null : Hydrator::restore(ProviderClient::class, [
            'accountId' => $account,
            'credentials' => new ClientCredentials($provider, $document['clientId'], $secret),
            'createdAt' => Documents::momentOf($document['createdAt']),
            'modifiedAt' => Documents::momentOf($document['modifiedAt']),
        ]);
    }

    public function save(ProviderClient $client): void
    {
        $state = Hydrator::stateOf($client);
        $credentials = $client->credentials();
        $account = $client->accountId()->value;

        $this->connectorsTable->transact([$this->connectorsTable->putting([
            'PK' => ConnectorsKeys::account($account),
            'SK' => ConnectorsKeys::client($credentials->provider->value),
            'client' => [
                'accountId' => $account,
                'provider' => $credentials->provider->value,
                'clientId' => $credentials->id,
                'secretVersion' => $this->accountSecrets->put($account, self::secretOf($credentials->provider), $credentials->secret),
                'createdAt' => Documents::moment($state['createdAt']),
                'modifiedAt' => Documents::moment($state['modifiedAt']),
            ],
        ])]);
    }

    public function remove(ProviderClient $client): void
    {
        $this->accountSecrets->forget($client->accountId()->value, self::secretOf($client->provider()));
        $this->connectorsTable->transact([$this->connectorsTable->deleting(ConnectorsKeys::account($client->accountId()->value), ConnectorsKeys::client($client->provider()->value))]);
    }

    private static function secretOf(Provider $provider): string
    {
        return sprintf('%s-client-secret', $provider->value);
    }
}
