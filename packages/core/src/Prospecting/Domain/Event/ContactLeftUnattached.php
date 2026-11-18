<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

/**
 * A contact to attach nobody attached in the 30 days after their last meeting: to erase, with what was kept for them
 * (ADR 0146), by their holder.
 */
final readonly class ContactLeftUnattached extends ContactToAttachEvent
{
}
