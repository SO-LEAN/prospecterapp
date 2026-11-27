<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Port;

use Prospecter\Core\Booking\Domain\Model\HostPresentation;

/**
 * Port: how a host presents themselves to bookers. The adapter translates it from PersonalData's published service
 * `PresentMembersService` (ADR 0045, 0089, 0153, 0223).
 */
interface HostPresentations
{
    /**
     * @return ?HostPresentation none for a person PersonalData does not present
     */
    public function presentationOf(string $personId): ?HostPresentation;
}
