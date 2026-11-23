<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Event;

use Prospecter\Core\Booking\Domain\Model\InactivePeriodId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The host added days when nobody can book them.
 */
final readonly class InactivePeriodAdded extends BookingPageEvent
{
    public function __construct(AccountId $accountId, MemberId $by, public InactivePeriodId $period, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $occurredAt);
    }
}
