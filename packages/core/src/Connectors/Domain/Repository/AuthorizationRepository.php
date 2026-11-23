<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Repository;

use Prospecter\Core\Connectors\Domain\Model\PendingAuthorization;

/**
 * The authorizations that wait for their provider's answer, found by their `state`. An expired authorization is
 * forgotten.
 */
interface AuthorizationRepository
{
    public function keep(PendingAuthorization $authorization): void;

    public function find(string $state): ?PendingAuthorization;

    public function forget(PendingAuthorization $authorization): void;
}
