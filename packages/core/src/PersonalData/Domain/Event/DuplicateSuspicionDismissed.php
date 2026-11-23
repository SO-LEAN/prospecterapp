<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Event;

use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * A member checked that the contact is not the other contact's duplicate. Neither will be suspected of being the other's
 * duplicate again.
 */
final readonly class DuplicateSuspicionDismissed extends PersonEvent
{
    public function __construct(AccountId $accountId, MemberId $by, PersonId $person, public PersonId $duplicate, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $person, $occurredAt);
    }
}
