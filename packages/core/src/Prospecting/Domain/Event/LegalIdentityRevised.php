<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\Event\OrganizationEvent;

/**
 * The legal identity of the organization was revised.
 */
final readonly class LegalIdentityRevised extends OrganizationEvent
{
}
