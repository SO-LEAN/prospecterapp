<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultExchangesToMakeConform;

/**
 * The exchanges of the timeline of an organization that are to make conform, their contact forgotten, the most
 * recent first, a slice after the other.
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
