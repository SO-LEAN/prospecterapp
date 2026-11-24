<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Agenda\ConsultWhatIsToProcess;

/**
 * Reads what the prospector has to process at the reading moment, which they give on their clock. It has the
 * exchanges they planned whose end has passed and that are still planned. It also has the to-dos they still have to
 * do that are due on that day or earlier.
 */
final readonly class ConsultWhatIsToProcess
{
    public function __construct(
        public string $prospectorId,
        public \DateTimeImmutable $at,
    ) {
    }
}
