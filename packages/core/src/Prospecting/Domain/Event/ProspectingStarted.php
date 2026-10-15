<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\Event\OrganizationEvent;

/**
 * The account started prospecting the organization.
 */
final readonly class ProspectingStarted extends OrganizationEvent
{
}
