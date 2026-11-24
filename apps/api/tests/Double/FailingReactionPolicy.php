<?php

declare(strict_types=1);

namespace Tests\App\Double;

use Prospecter\Core\Prospecting\Domain\Event\ObservationsWritten;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * The policy of a subscription of the tests (`prospecting-failing-reaction`): written observations send the command
 * whose handler fails when a test asks it to (FailingReaction).
 */
final readonly class FailingReactionPolicy implements Policy
{
    public function __invoke(ObservationsWritten $event): ReactToWrittenObservations
    {
        return new ReactToWrittenObservations($event->organization->value, $event->occurredAt);
    }
}
