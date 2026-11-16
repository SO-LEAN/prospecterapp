<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultWhatIsToProcess;

/**
 * What the prospector has to process at the moment of the reading, given on their clock: the exchanges they planned
 * whose end has passed, still planned, and the to-dos they still have to do of that day or of the days before.
 */
final readonly class ConsultWhatIsToProcess
{
    public function __construct(
        public string $prospectorId,
        public \DateTimeImmutable $at,
    ) {
    }
}
