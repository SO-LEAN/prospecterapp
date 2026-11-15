<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultExchangesToProcess;

/**
 * The exchanges the prospector planned whose end has passed at the moment of the reading, still planned.
 */
final readonly class ConsultExchangesToProcess
{
    public function __construct(
        public string $prospectorId,
        public \DateTimeImmutable $at,
    ) {
    }
}
