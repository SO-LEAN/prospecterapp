<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Uuid;

/**
 * An appointment's id. The client that books the appointment gives it. The exchange that the appointment becomes takes
 * the same id (ADR 0164).
 */
final readonly class AppointmentId extends Uuid
{
}
