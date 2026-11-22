<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The contact is suspected of being a duplicate of another contact of their organization, whom they resemble.
 */
final readonly class DuplicateSuspected extends PersonEvent
{
    public function __construct(AccountId $accountId, MemberId $by, PersonId $person, public PersonId $duplicate, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $person, $occurredAt);
    }
}
