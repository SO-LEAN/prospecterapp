<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\RecognizeCallingOrganization;

/**
 * Which of the organizations I prospect has the number calling me.
 */
final readonly class RecognizeCallingOrganization
{
    public function __construct(
        public string $prospectorId,
        public string $phoneNumber,
    ) {
    }
}
