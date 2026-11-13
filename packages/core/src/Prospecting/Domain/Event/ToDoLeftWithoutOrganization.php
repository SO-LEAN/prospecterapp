<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

/**
 * The to-do is kept for no organization of the account any more: it must not stay.
 */
final readonly class ToDoLeftWithoutOrganization extends ToDoEvent
{
}
