<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Exception;

use Prospecter\Core\Booking\Domain\Model\InactivePeriodId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;

/**
 * The host's booking page has no inactive period with this id. The period was already removed, or never added.
 */
final class UnknownInactivePeriod extends NotFound
{
    public static function identifiedBy(InactivePeriodId $id): self
    {
        return new self(sprintf('Inactive period %s not found', $id));
    }
}
