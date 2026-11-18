<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultContactToAttach;

/**
 * Whether the person waits for this prospector to attach them, and until when (ADR 0146): the banner of their page.
 */
final readonly class ConsultContactToAttach
{
    public function __construct(
        public string $prospectorId,
        public string $personId,
    ) {
    }
}
