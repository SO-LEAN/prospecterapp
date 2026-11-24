<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Identification\RecognizeCallingOrganization;

/**
 * Finds which of the organizations I prospect has the number that is calling me.
 */
final readonly class RecognizeCallingOrganization
{
    public function __construct(
        public string $prospectorId,
        public string $phoneNumber,
    ) {
    }
}
