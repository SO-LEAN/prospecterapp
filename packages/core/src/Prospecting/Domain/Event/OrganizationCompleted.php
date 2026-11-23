<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

/**
 * Prospecter filled what an organization lacked and kept what was already recorded (ADR 0177).
 */
final readonly class OrganizationCompleted extends OrganizationEvent
{
}
