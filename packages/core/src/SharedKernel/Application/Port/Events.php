<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application\Port;

/**
 * Port: delivers the events that the aggregates recorded. The use case releases them once it saves the aggregates
 * (ADR 0042). The port delivers them synchronously, inside the use case: when a reacting context refuses, the
 * whole use case refuses.
 */
interface Events
{
    public function publish(object ...$events): void;
}
