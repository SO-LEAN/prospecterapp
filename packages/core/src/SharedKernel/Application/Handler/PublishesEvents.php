<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application\Handler;

use Prospecter\Core\SharedKernel\Domain\Event\ReleasesEvents;

/**
 * The use case's side of the domain events (ADR 0043). Once the handler saves an aggregate, it releases the
 * aggregate's recorded events and publishes them on the Events port, which the handler receives as `$events`. The
 * command's unit of work keeps what the handler saves and what the other contexts write in reaction, all at once or
 * not at all.
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
