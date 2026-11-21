<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\Event\OrganizationEvent;

/**
 * What an organization lacked was filled, what was noted kept (ADR 0177).
 */
final readonly class OrganizationCompleted extends OrganizationEvent
{
}
