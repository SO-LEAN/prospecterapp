<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

/**
 * Nobody attached a contact to attach in the 30 days after their last meeting. The contact is to be erased, with what
 * was kept for them (ADR 0146). Their holder is the event's author.
 */
final readonly class ContactLeftUnattached extends ContactToAttachEvent
{
}
