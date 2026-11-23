<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

/**
 * A contact to attach booked a meeting through another member's public link. That member holds them from then on (ADR
 * 0164).
 */
final readonly class ContactToAttachHandedOver extends ContactToAttachEvent
{
}
