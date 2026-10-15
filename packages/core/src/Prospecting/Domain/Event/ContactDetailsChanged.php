<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\Event\OrganizationEvent;

/**
 * The general contact details of the organization changed.
 */
final readonly class ContactDetailsChanged extends OrganizationEvent
{
}
