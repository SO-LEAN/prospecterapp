<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Connectors\Domain\Exception\NoConnection;
use Prospecter\Core\Connectors\Domain\Model\Connection;
use Prospecter\Core\Connectors\Domain\Model\MailImport;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The connections as the scenarios keep them, a read answering a connection as it was read before when a scenario
 * says so: a push that read it before a change writes after it.
 */
final class InMemoryConnectionRepository implements ConnectionRepository
{
    private ?Connection $readBefore = null;

    public function __construct(private readonly InMemoryDatabase $database)
    {
    }

    /**
     * The next read of a connection of its member answers it as given, read before what happens since.
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
        return array_find($this->database->connections(), static function (Connection $connection) use ($account, $provider, $mailbox): bool {
            $state = StateOf::the($connection);

            return $state->value('accountId')->equals($account) && $provider === $state->value('provider') && $state->value('externalAccount')->equals($mailbox) && MailImport::Off !== $state->value('mailImport');
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
