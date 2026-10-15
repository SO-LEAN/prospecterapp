<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\Event\PersonEvent;

/**
 * A contact was erased: no organization may name it any more.
 */
final readonly class ContactForgotten extends PersonEvent
{
}
