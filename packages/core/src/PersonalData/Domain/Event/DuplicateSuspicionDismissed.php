<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Event\PersonEvent;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Checked by a member, the contact is no duplicate of the other: neither is suspected of being the other's again.
 */
final readonly class DuplicateSuspicionDismissed extends PersonEvent
{
    public function __construct(AccountId $accountId, MemberId $by, PersonId $person, public PersonId $duplicate, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $person, $occurredAt);
    }
}
