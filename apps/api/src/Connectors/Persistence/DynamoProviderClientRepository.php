<?php

declare(strict_types=1);

namespace App\Connectors\Persistence;

use App\Shared\Persistence\AccountSecrets;
use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Connectors\Domain\Exception\NoClient;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\MailboxNotifications;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\ProviderClient;
use Prospecter\Core\Connectors\Domain\Repository\ProviderClientRepository;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * An account's client at a provider (ADR 0125). The Connectors table keeps the client's id and its secret's version.
 * The secret itself is an SSM parameter. A client whose secret is gone is no client. The table also keeps, while the
 * client has them, the topic that its mailboxes notify Prospecter through and the service account that pushes the
 * notifications (ADR 0154).
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
        $item = $this->connectorsTable->item(ConnectorsKeys::account($account->value), ConnectorsKeys::client($provider->value));
        $document = null === $item ? null : Typed::document($item['client']);
        $secret = null === $document ? null : $this->accountSecrets->find($account->value, self::secretOf($provider), Typed::int($document['secretVersion']));

        return null === $secret ? null : Hydrator::restore(ProviderClient::class, [
            'accountId' => $account,
            'credentials' => new ClientCredentials($provider, Typed::string($document['clientId']), $secret),
            'mailboxNotifications' => self::mailboxNotificationsOf($document),
            'createdAt' => Documents::momentOf(Typed::string($document['createdAt'])),
            'modifiedAt' => Documents::momentOf(Typed::string($document['modifiedAt'])),
        ]);
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function mailboxNotificationsOf(array $document): ?MailboxNotifications
    {
        $topic = Typed::stringOrNull($document['mailboxTopic'] ?? null);

        return null === $topic ? null : new MailboxNotifications($topic, new Email(Typed::string($document['mailboxPushAccount'])));
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
                'mailboxTopic' => $client->mailboxNotifications()?->topic,
                'mailboxPushAccount' => $client->mailboxNotifications()?->pushAccount->value,
                'createdAt' => Documents::moment(Typed::object($state['createdAt'], \DateTimeImmutable::class)),
                'modifiedAt' => Documents::moment(Typed::object($state['modifiedAt'], \DateTimeImmutable::class)),
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
