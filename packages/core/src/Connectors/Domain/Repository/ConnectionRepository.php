<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Repository;

use Prospecter\Core\Connectors\Domain\Exception\NoConnection;
use Prospecter\Core\Connectors\Domain\Model\Connection;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MemberId;

interface ConnectionRepository
{
    /**
     * @throws NoConnection
     */
    public function get(MemberId $member, Provider $provider): Connection;

    public function find(MemberId $member, Provider $provider): ?Connection;

    /**
     * @return list<Connection>
     */
    public function of(MemberId $member): array;

    /**
     * The connection of the account's member who imports this mailbox's e-mails (ADR 0154). Its import is active or to
     * reconnect. There is none while the import is off.
     */
    public function importing(AccountId $account, Provider $provider, Email $mailbox): ?Connection;

    public function save(Connection $connection): void;

    /**
     * Replaces the kept connection with this one, only while the kept one is still the same (ADR 0133, 0140). Its
     * member may have disconnected in the meantime: the connection is not brought back. They may have connected again:
     * the new connection is not overwritten. Either way, the method throws, so that the reaction is tried again. The
     * retry then finds no connection, or the new one.
     */
    public function replace(Connection $connection): void;

    public function remove(Connection $connection): void;
}
