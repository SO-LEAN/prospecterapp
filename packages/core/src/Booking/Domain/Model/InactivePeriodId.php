<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Uuid;

/**
 * The identity of an inactive period of a booking page, given by the client that adds it.
 */
final readonly class InactivePeriodId extends Uuid
{
}
