<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultTimelineIntents;

/**
 * Tells what the prospector may do with a person's timeline, without reading it. A contact's card leads to the
 * timeline only when the prospector may read it.
 */
final readonly class ConsultTimelineIntents
{
    public function __construct(
        public string $prospectorId,
        public string $personId,
    ) {
    }
}
