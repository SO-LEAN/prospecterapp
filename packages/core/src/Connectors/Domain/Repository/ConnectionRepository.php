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
     * The connection of a member of the account importing the e-mails of this mailbox, active or to reconnect; none
     * while its import is off (ADR 0154).
     */
    public function importing(AccountId $account, Provider $provider, Email $mailbox): ?Connection;

    public function save(Connection $connection): void;

    /**
     * Puts the connection in place of itself while it is still the one kept: one its member disconnected meanwhile is
     * not brought back, nor one they connected again meanwhile overwritten, the failure thrown for the reaction to be
     * tried again, which then finds none, or the new one (ADR 0133, 0140).
     */
    public function replace(Connection $connection): void;

    public function remove(Connection $connection): void;
}
