<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ToDo\ConsultToDo;

/**
 * Reads one to-do and what its state offers.
 */
final readonly class ConsultToDo
{
    public function __construct(
        public string $prospectorId,
        public string $id,
    ) {
    }
}
