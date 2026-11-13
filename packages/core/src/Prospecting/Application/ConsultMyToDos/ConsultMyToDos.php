<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultMyToDos;

/**
 * The to-dos the prospector holds that are still to do, the soonest due first.
 */
final readonly class ConsultMyToDos
{
    public function __construct(
        public string $prospectorId,
    ) {
    }
}
