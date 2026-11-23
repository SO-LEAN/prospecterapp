<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Connectors\Domain\Exception\NoConnection;
use Prospecter\Core\Connectors\Domain\Model\Connection;
use Prospecter\Core\Connectors\Domain\Model\MailImport;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Keeps the connections as the scenarios do. When a scenario says so, a read answers a connection as it was read
 * before. So a push that read the connection before a change writes after that change.
 */
final class InMemoryConnectionRepository implements ConnectionRepository
{
    private ?Connection $readBefore = null;

    public function __construct(private readonly InMemoryDatabase $database)
    {
    }

    /**
     * The next read of the member's connection answers the given connection, as it was read before the later changes.
     */
    public function answerTheNextReadWith(Connection $readBefore): void
    {
        $this->readBefore = clone $readBefore;
    }

    public function get(MemberId $member, Provider $provider): Connection
    {
        return $this->find($member, $provider) ?? throw NoConnection::to($provider);
    }

    public function find(MemberId $member, Provider $provider): ?Connection
    {
        if (null !== $this->readBefore && $this->readBefore->member()->equals($member)) {
            [$readBefore, $this->readBefore] = [$this->readBefore, null];

            return $readBefore;
        }

        return $this->database->connection($member->value, $provider->value);
    }

    public function of(MemberId $member): array
    {
        return $this->database->connectionsOf($member->value);
    }

    public function importing(AccountId $account, Provider $provider, Email $mailbox): ?Connection
    {
        return array_find($this->database->connections(), static function (Connection $connection) use ($account, $mailbox): bool {
            $state = StateOf::the($connection);

            return $state->object('accountId', AccountId::class)->equals($account) && $state->object('externalAccount', Email::class)->equals($mailbox) && MailImport::Off !== $state->object('mailImport', MailImport::class);
        });
    }

    public function save(Connection $connection): void
    {
        $this->database->storeConnection($connection);
    }

    public function replace(Connection $connection): void
    {
        $kept = $this->database->connection($connection->member()->value, $connection->provider()->value);
        if (null === $kept || !$kept->isTheSameAs($connection)) {
            throw new \RuntimeException('The connection was forgotten, or made again, meanwhile');
        }

        $this->save($connection);
    }

    public function remove(Connection $connection): void
    {
        $this->database->forgetConnection($connection);
    }
}
