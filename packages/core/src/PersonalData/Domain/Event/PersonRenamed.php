<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\Event\PersonEvent;

/**
 * The first or last name of the person changed.
 */
final readonly class PersonRenamed extends PersonEvent
{
}
