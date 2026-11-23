<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Event;

/**
 * The fields a contact lacked were filled, and the logged ones were kept (ADR 0177).
 */
final readonly class ContactCompleted extends PersonEvent
{
}
