<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Event;

use Prospecter\Core\Booking\Domain\Model\InactivePeriodId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The host removed an inactive period: those days are offered again, as their windows say.
 */
final readonly class InactivePeriodRemoved extends BookingPageEvent
{
    public function __construct(AccountId $accountId, MemberId $by, public InactivePeriodId $period, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $occurredAt);
    }
}
