<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\Event\OrganizationEvent;

/**
 * The logo of the organization changed.
 */
final readonly class LogoChanged extends OrganizationEvent
{
}
