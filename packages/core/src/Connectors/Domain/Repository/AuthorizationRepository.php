<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Repository;

use Prospecter\Core\Connectors\Domain\Model\PendingAuthorization;

/**
 * The authorizations waiting for the answer of their provider, by their `state`, forgotten once expired.
 */
interface AuthorizationRepository
{
    public function keep(PendingAuthorization $authorization): void;

    public function find(string $state): ?PendingAuthorization;

    public function forget(PendingAuthorization $authorization): void;
}
