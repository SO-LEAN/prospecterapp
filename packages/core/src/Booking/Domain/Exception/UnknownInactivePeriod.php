<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Exception;

use Prospecter\Core\Booking\Domain\Model\InactivePeriodId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;

/**
 * The booking page of the host has no inactive period of this identity: removed already, or never added.
 */
final class UnknownInactivePeriod extends NotFound
{
    public static function identifiedBy(InactivePeriodId $id): self
    {
        return new self(sprintf('Inactive period %s not found', $id));
    }
}
