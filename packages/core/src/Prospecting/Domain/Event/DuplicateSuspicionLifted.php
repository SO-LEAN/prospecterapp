<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Event\OrganizationEvent;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * The organization no longer resembles the one it was suspected of being a duplicate of.
 */
final readonly class DuplicateSuspicionLifted extends OrganizationEvent
{
    public function __construct(AccountId $accountId, MemberId $by, OrganizationId $organization, public OrganizationId $duplicate, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $organization, $occurredAt);
    }
}
