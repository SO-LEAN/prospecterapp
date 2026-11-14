<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Connectors\Domain\Model\PendingAuthorization;
use Prospecter\Core\Connectors\Domain\Repository\AuthorizationRepository;

/**
 * The authorizations kept until they are forgotten: the expired ones too, as the table keeps an item a while after
 * its expiry, the rule deciding that they no longer wait.
 */
final readonly class InMemoryAuthorizationRepository implements AuthorizationRepository
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function keep(PendingAuthorization $authorization): void
    {
        $this->database->keepAuthorization($authorization);
    }

    public function find(string $state): ?PendingAuthorization
    {
        return $this->database->authorization($state);
    }

    public function forget(PendingAuthorization $authorization): void
    {
        $this->database->forgetAuthorization($authorization->state);
    }
}
