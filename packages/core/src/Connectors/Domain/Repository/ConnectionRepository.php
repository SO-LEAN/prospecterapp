<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Repository;

use Prospecter\Core\Connectors\Domain\Exception\NoConnection;
use Prospecter\Core\Connectors\Domain\Model\Connection;
use Prospecter\Core\Connectors\Domain\Model\Provider;
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

    public function save(Connection $connection): void;

    public function remove(Connection $connection): void;
}
