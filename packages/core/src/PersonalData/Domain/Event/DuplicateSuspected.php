<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Event;

use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The contact resembles another contact of their organization, and is suspected of being that contact's duplicate.
 */
final readonly class DuplicateSuspected extends PersonEvent
{
    public function __construct(AccountId $accountId, MemberId $by, PersonId $person, public PersonId $duplicate, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $person, $occurredAt);
    }
}
