<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

/**
 * A person no organization names took a meeting with a member, who holds them until they attach them.
 */
final readonly class ContactToAttachMet extends ContactToAttachEvent
{
}
