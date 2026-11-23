<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Uuid;

/**
 * A booking page's inactive period's id. The client that adds the period gives it.
 */
final readonly class InactivePeriodId extends Uuid
{
}
