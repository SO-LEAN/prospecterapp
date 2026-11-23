<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Event;

/**
 * The member's calendar « Prospecter » was created again, because the provider answered that the former one was gone.
 */
final readonly class CalendarRecreated extends CalendarToFill
{
}
