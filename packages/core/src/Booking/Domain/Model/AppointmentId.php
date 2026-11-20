<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Uuid;

/**
 * The identity of an appointment, given by the client that books it; the exchange it becomes takes it too (ADR 0164).
 */
final readonly class AppointmentId extends Uuid
{
}
