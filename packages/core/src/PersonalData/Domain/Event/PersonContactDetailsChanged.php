<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Event;

/**
 * The email, phone number or language of the person changed.
 */
final readonly class PersonContactDetailsChanged extends PersonEvent
{
}
