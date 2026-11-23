<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Event;

/**
 * A contact was erased. No organization may name it any more.
 */
final readonly class ContactForgotten extends PersonEvent
{
}
