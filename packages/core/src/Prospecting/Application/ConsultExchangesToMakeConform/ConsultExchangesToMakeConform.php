<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultExchangesToMakeConform;

/**
 * Reads the exchanges in an organization's timeline that must be redacted because their contact is forgotten. The most
 * recent come first, one slice at a time.
 */
final readonly class ConsultExchangesToMakeConform
{
    public function __construct(
        public string $prospectorId,
        public string $organizationId,
        public ?string $after = null,
        public int $max = 20,
    ) {
    }
}
