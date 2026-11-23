<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultToDos;

/**
 * Reads a contact's to-dos. The to-dos still to do come first, then those done.
 */
final readonly class ConsultToDos
{
    public function __construct(
        public string $prospectorId,
        public string $personId,
    ) {
    }
}
