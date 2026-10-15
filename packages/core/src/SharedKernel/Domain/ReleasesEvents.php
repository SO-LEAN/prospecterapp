<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;

/**
 * An aggregate: it records what happens to it, and gives the events to the use case once saved (ADR 0043).
 */
interface ReleasesEvents
{
    /**
     * @return list<DomainEvent> the events recorded since the last release, oldest first
     */
    public function releaseEvents(): array;
}
