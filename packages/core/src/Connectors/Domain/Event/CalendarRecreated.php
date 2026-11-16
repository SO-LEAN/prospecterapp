<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Event;

/**
 * The calendar « Prospecter » of the member was created again, the provider answering that the former one was gone.
 */
final readonly class CalendarRecreated extends CalendarToFill
{
}
