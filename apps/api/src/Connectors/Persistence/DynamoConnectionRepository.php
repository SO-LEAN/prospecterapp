<?php

namespace App\Connectors\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use App\Shared\Persistence\Table;
use Prospecter\Core\Connectors\Domain\Exception\NoConnection;
use Prospecter\Core\Connectors\Domain\Model\Connection;
use Prospecter\Core\Connectors\Domain\Model\ConnectionState;
use Prospecter\Core\Connectors\Domain\Model\MailImport;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The connections in the table of Connectors (ADR 0120): `MEMBER#<member>` / `CONNECTION#<provider>`, the
 * connection as a document, its refresh token encrypted at rest by DynamoDB only, the calendar « Prospecter » once
 * created (ADR 0127).
 */
final readonly class DynamoConnectionRepository implements ConnectionRepository
{
    /** the client of the instance the connections made before ADR 0125 were granted to, which no account has */
    private const string CLIENT_OF_THE_INSTANCE = 'the-client-of-the-instance';
    /** `connection` a reserved word of DynamoDB, named by a placeholder */
    private const string STILL_THE_SAME = 'attribute_exists(PK) AND #connection.connectedAt = :connectedAt';

    public function __construct(private Table $connectorsTable)
    {
    }

    public function get(MemberId $member, Provider $provider): Connection
    {
        return $this->find($member, $provider) ?? throw NoConnection::to($provider);
    }

    public function find(MemberId $member, Provider $provider): ?Connection
    {
        $item = $this->connectorsTable->item(ConnectorsKeys::member($member->value), ConnectorsKeys::connection($provider->value));

        return null === $item ? null : self::restore($item['connection']);
    }

    public function of(MemberId $member): array
    {
        return array_map(static fn (array $item): Connection => self::restore($item['connection']), $this->connectorsTable->query(ConnectorsKeys::member($member->value)));
    }

    public function importing(AccountId $account, Provider $provider, Email $mailbox): ?Connection
    {
        $item = $this->connectorsTable->query(ConnectorsKeys::mailbox($account->value, $provider->value, $mailbox->value), Table::BY_GSI2)[0] ?? null;

        return null === $item ? null : self::restore($item['connection']);
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function restore(array $document): Connection
    {
        return Hydrator::restore(Connection::class, [
            'member' => new MemberId($document['member']),
            'accountId' => new AccountId($document['accountId']),
            'provider' => Provider::from($document['provider']),
            'externalAccount' => new Email($document['externalAccount']),
            'refreshToken' => new RefreshToken($document['refreshToken']),
            'clientId' => $document['clientId'] ?? self::CLIENT_OF_THE_INSTANCE,
            'state' => ConnectionState::from($document['state']),
            'calendar' => $document['calendar'] ?? null,
            'mailImport' => MailImport::from($document['mailImport'] ?? MailImport::Off->value),
            'historyId' => $document['historyId'] ?? null,
            'watchExpiresAt' => Documents::momentOfOrNone($document['watchExpiresAt'] ?? null),
            'notifiedHistoryId' => $document['notifiedHistoryId'] ?? null,
            'lastNotifiedAt' => Documents::momentOfOrNone($document['lastNotifiedAt'] ?? null),
            'connectedAt' => Documents::momentOf($document['connectedAt']),
            'modifiedAt' => Documents::momentOf($document['modifiedAt']),
        ]);
    }

    public function save(Connection $connection): void
    {
        $this->connectorsTable->transact([$this->connectorsTable->putting(self::itemOf($connection))]);
    }

    /**
     * Conditioned on the item still there, connected at the same moment: a connection removed meanwhile, or made
     * again, fails the commit (`ConditionFailed`).
     */
    public function replace(Connection $connection): void
    {
        $item = self::itemOf($connection);

        $this->connectorsTable->transact([$this->connectorsTable->replacing($item, self::STILL_THE_SAME, [':connectedAt' => Documents::moment(Hydrator::stateOf($connection)['connectedAt'])], ['#connection' => 'connection'])]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function itemOf(Connection $connection): array
    {
        $state = Hydrator::stateOf($connection);

        return [
            'PK' => ConnectorsKeys::member($connection->member()->value),
            'SK' => ConnectorsKeys::connection($connection->provider()->value),
            ...self::keysOfTheImport($state),
            'connection' => [
                'member' => (string) $state['member'],
                'accountId' => (string) $state['accountId'],
                'provider' => $state['provider']->value,
                'externalAccount' => (string) $state['externalAccount'],
                'refreshToken' => (string) $state['refreshToken'],
                'clientId' => $state['clientId'],
                'state' => $state['state']->value,
                'calendar' => $state['calendar'],
                'mailImport' => $state['mailImport']->value,
                'historyId' => $state['historyId'],
                'watchExpiresAt' => Documents::momentOrNone($state['watchExpiresAt']),
                'notifiedHistoryId' => $state['notifiedHistoryId'],
                'lastNotifiedAt' => Documents::momentOrNone($state['lastNotifiedAt']),
                'connectedAt' => Documents::moment($state['connectedAt']),
                'modifiedAt' => Documents::moment($state['modifiedAt']),
            ],
        ];
    }

    /**
     * While its import is not off, the connection is found by its mailbox in its account, under `GSI2`, the
     * notifications of the mailbox naming only its address; while it is active, it is listed for the daily renewal
     * of its watch, under `GSI1` (ADR 0154).
     *
     * @param array<string, mixed> $state
     *
     * @return array<string, string>
     */
    private static function keysOfTheImport(array $state): array
    {
        if (MailImport::Off === $state['mailImport']) {
            return [];
        }

        $member = ConnectorsKeys::member((string) $state['member']);
        $renewed = MailImport::Active === $state['mailImport'] ? ['GSI1PK' => ConnectorsKeys::MAIL_IMPORTS, 'GSI1SK' => $member.'#'.$state['provider']->value] : [];

        return [
            'GSI2PK' => ConnectorsKeys::mailbox((string) $state['accountId'], $state['provider']->value, (string) $state['externalAccount']),
            'GSI2SK' => $member,
            ...$renewed,
        ];
    }

    public function remove(Connection $connection): void
    {
        $this->connectorsTable->transact([$this->connectorsTable->deleting(ConnectorsKeys::member($connection->member()->value), ConnectorsKeys::connection($connection->provider()->value))]);
    }
}
