<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Event;

/**
 * The person's email, phone number or language changed.
 */
final readonly class PersonContactDetailsChanged extends PersonEvent
{
}
