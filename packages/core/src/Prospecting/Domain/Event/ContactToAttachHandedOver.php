<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

/**
 * A contact to attach booked a meeting by the public link of another member, who holds them from then on (ADR 0164).
 */
final readonly class ContactToAttachHandedOver extends ContactToAttachEvent
{
}
