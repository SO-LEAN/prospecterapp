<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * A member checked that the organization is no duplicate of the one it resembles. Prospecter never suspects it of that
 * again.
 */
final readonly class DuplicateSuspicionDismissed extends OrganizationEvent
{
    public function __construct(AccountId $accountId, MemberId $by, OrganizationId $organization, public OrganizationId $duplicate, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $organization, $occurredAt);
    }
}
