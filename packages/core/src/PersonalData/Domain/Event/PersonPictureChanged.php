<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\Event\PersonEvent;

/**
 * The picture of the person changed.
 */
final readonly class PersonPictureChanged extends PersonEvent
{
}
