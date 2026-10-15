<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\Event\PersonEvent;

/**
 * What the person does in their organization changed.
 */
final readonly class JobTitleChanged extends PersonEvent
{
}
