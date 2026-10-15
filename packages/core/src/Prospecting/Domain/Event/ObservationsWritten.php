<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\Event\OrganizationEvent;

/**
 * The observations on the organization were written or erased.
 */
final readonly class ObservationsWritten extends OrganizationEvent
{
}
