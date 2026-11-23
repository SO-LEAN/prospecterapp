<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

/**
 * The to-do is no longer kept for any organization of the account. It must not stay.
 */
final readonly class ToDoLeftWithoutOrganization extends ToDoEvent
{
}
