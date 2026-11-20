<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Event\PersonEvent;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The contact is no longer suspected of being a duplicate of another: they no longer resemble each other, or one is
 * forgotten.
 */
final readonly class DuplicateSuspicionLifted extends PersonEvent
{
    public function __construct(AccountId $accountId, MemberId $by, PersonId $person, public PersonId $duplicate, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $person, $occurredAt);
    }
}
