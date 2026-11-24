<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Contact\ConsultContactToAttach;

/**
 * Tells whether the person waits for this prospector to attach them, and until when (ADR 0146). Their page shows
 * this in its banner.
 */
final readonly class ConsultContactToAttach
{
    public function __construct(
        public string $prospectorId,
        public string $personId,
    ) {
    }
}
