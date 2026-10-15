<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application\Port;

/**
 * Port: delivers the events the aggregates recorded, released by the use case once they are saved
 * (ADR 0042). Delivered synchronously, within the use case: a refusal of a context that reacts refuses the
 * whole use case.
 */
interface Events
{
    public function publish(object ...$events): void;
}
