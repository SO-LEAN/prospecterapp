<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

/**
 * A person whom no organization names took a meeting with a member. That member holds them until a member attaches
 * them.
 */
final readonly class ContactToAttachMet extends ContactToAttachEvent
{
}
