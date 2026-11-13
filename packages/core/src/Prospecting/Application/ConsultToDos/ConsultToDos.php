<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultToDos;

/**
 * The to-dos for a contact, those to do then those done.
 */
final readonly class ConsultToDos
{
    public function __construct(
        public string $prospectorId,
        public string $personId,
    ) {
    }
}
