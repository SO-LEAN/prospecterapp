<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultTimelineIntents;

/**
 * What the prospector may do with the timeline of a person, without reading it: the card of a contact leads to
 * its timeline only when it may be read.
 */
final readonly class ConsultTimelineIntents
{
    public function __construct(
        public string $prospectorId,
        public string $personId,
    ) {
    }
}
