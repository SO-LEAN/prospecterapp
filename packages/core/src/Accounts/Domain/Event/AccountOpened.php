<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Event\AccountEvent;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * An organization opened its account; its founder becomes its first member, with the profile named.
 */
final readonly class AccountOpened extends AccountEvent
{
    public function __construct(AccountId $accountId, MemberId $by, public PersonId $founderProfile, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $occurredAt);
    }
}
