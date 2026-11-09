<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application;

use Prospecter\Core\SharedKernel\Domain\ReleasesEvents;

/**
 * The use case side of the domain events (ADR 0043): once an aggregate is saved, its recorded events are
 * released and published on the port Events, that the handler receives as `$events`. What the handler saves and
 * what its events make the other contexts write are kept all at once or not at all, in the unit of work of the
 * command.
 */
trait PublishesEvents
{
    private function publishWhatHappenedTo(ReleasesEvents ...$aggregates): void
    {
        foreach ($aggregates as $aggregate) {
            $this->events->publish(...$aggregate->releaseEvents());
        }
    }
}
