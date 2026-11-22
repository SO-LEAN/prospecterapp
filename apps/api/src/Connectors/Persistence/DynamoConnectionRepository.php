<?php

declare(strict_types=1);

namespace App\Connectors\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use App\Shared\Persistence\Table;
use App\Shared\Typed;
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

    private static function restore(mixed $document): Connection
    {
        $document = Typed::document($document);

        return Hydrator::restore(Connection::class, [
            'member' => new MemberId(Typed::string($document['member'])),
            'accountId' => new AccountId(Typed::string($document['accountId'])),
            'provider' => Provider::from(Typed::string($document['provider'])),
            'externalAccount' => new Email(Typed::string($document['externalAccount'])),
            'refreshToken' => new RefreshToken(Typed::string($document['refreshToken'])),
            'clientId' => Typed::stringOrNull($document['clientId'] ?? null) ?? self::CLIENT_OF_THE_INSTANCE,
            'state' => ConnectionState::from(Typed::string($document['state'])),
            'calendar' => Typed::stringOrNull($document['calendar'] ?? null),
            'mailImport' => MailImport::from(Typed::stringOrNull($document['mailImport'] ?? null) ?? MailImport::Off->value),
            'historyId' => Typed::stringOrNull($document['historyId'] ?? null),
            'watchExpiresAt' => Documents::momentOfOrNone(Typed::stringOrNull($document['watchExpiresAt'] ?? null)),
            'notifiedHistoryId' => Typed::stringOrNull($document['notifiedHistoryId'] ?? null),
            'lastNotifiedAt' => Documents::momentOfOrNone(Typed::stringOrNull($document['lastNotifiedAt'] ?? null)),
            'historyReadAt' => Documents::momentOfOrNone(Typed::stringOrNull($document['historyReadAt'] ?? null)),
            'connectedAt' => Documents::momentOf(Typed::string($document['connectedAt'])),
            'modifiedAt' => Documents::momentOf(Typed::string($document['modifiedAt'])),
        ]);
    }

    public function save(Connection $connection): void
    {
        $this->connectorsTable->transact([$this->connectorsTable->putting(self::itemOf(self::documentOf($connection)))]);
    }

    /**
     * Conditioned on the item still there, connected at the same moment: a connection removed meanwhile, or made
     * again, fails the commit (`ConditionFailed`).
     */
    public function replace(Connection $connection): void
    {
        $document = self::documentOf($connection);

        $this->connectorsTable->transact([$this->connectorsTable->replacing(self::itemOf($document), self::STILL_THE_SAME, [':connectedAt' => $document['connectedAt']], ['#connection' => 'connection'])]);
    }

    /**
     * @param array{member: string, accountId: string, provider: string, externalAccount: string, mailImport: string, ...} $document
     *
     * @return array{PK: string, SK: string, ...}
     */
    private static function itemOf(array $document): array
    {
        return [
            'PK' => ConnectorsKeys::member($document['member']),
            'SK' => ConnectorsKeys::connection($document['provider']),
            'connection' => $document,
        ] + self::keysOfTheImport($document);
    }

    /**
     * @return array{member: string, accountId: string, provider: string, externalAccount: string, refreshToken: string, clientId: string, state: string, calendar: ?string, mailImport: string, historyId: ?string, watchExpiresAt: ?string, notifiedHistoryId: ?string, lastNotifiedAt: ?string, historyReadAt: ?string, connectedAt: string, modifiedAt: string}
     */
    private static function documentOf(Connection $connection): array
    {
        $state = Hydrator::stateOf($connection);

        return [
            'member' => Typed::object($state['member'], MemberId::class)->value,
            'accountId' => Typed::object($state['accountId'], AccountId::class)->value,
            'provider' => Typed::object($state['provider'], Provider::class)->value,
            'externalAccount' => Typed::object($state['externalAccount'], Email::class)->value,
            'refreshToken' => Typed::object($state['refreshToken'], RefreshToken::class)->value,
            'clientId' => Typed::string($state['clientId']),
            'state' => Typed::object($state['state'], ConnectionState::class)->value,
            'calendar' => Typed::stringOrNull($state['calendar']),
            'mailImport' => Typed::object($state['mailImport'], MailImport::class)->value,
            'historyId' => Typed::stringOrNull($state['historyId']),
            'watchExpiresAt' => Documents::momentOrNone(Typed::objectOrNull($state['watchExpiresAt'], \DateTimeImmutable::class)),
            'notifiedHistoryId' => Typed::stringOrNull($state['notifiedHistoryId']),
            'lastNotifiedAt' => Documents::momentOrNone(Typed::objectOrNull($state['lastNotifiedAt'], \DateTimeImmutable::class)),
            'historyReadAt' => Documents::momentOrNone(Typed::objectOrNull($state['historyReadAt'], \DateTimeImmutable::class)),
            'connectedAt' => Documents::moment(Typed::object($state['connectedAt'], \DateTimeImmutable::class)),
            'modifiedAt' => Documents::moment(Typed::object($state['modifiedAt'], \DateTimeImmutable::class)),
        ];
    }

    /**
     * While its import is not off, the connection is found by its mailbox in its account, under `GSI2`, the
     * notifications of the mailbox naming only its address; while it is active, it is listed for the daily renewal
     * of its watch, under `GSI1` (ADR 0154).
     *
     * @param array{member: string, accountId: string, provider: string, externalAccount: string, mailImport: string, ...} $document
     *
     * @return array<string, string>
     */
    private static function keysOfTheImport(array $document): array
    {
        if (MailImport::Off->value === $document['mailImport']) {
            return [];
        }

        $member = ConnectorsKeys::member($document['member']);
        $renewed = MailImport::Active->value === $document['mailImport'] ? ['GSI1PK' => ConnectorsKeys::MAIL_IMPORTS, 'GSI1SK' => $member.'#'.$document['provider']] : [];

        return [
            'GSI2PK' => ConnectorsKeys::mailbox($document['accountId'], $document['provider'], $document['externalAccount']),
            'GSI2SK' => $member,
            ...$renewed,
        ];
    }

    public function remove(Connection $connection): void
    {
        $this->connectorsTable->transact([$this->connectorsTable->deleting(ConnectorsKeys::member($connection->member()->value), ConnectorsKeys::connection($connection->provider()->value))]);
    }
}
