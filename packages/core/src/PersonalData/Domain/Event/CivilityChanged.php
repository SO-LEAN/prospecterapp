<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\Event\PersonEvent;

/**
 * How one addresses the contact changed, or is no longer noted.
 */
final readonly class CivilityChanged extends PersonEvent
{
}
