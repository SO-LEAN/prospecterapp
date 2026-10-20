<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Event\AccountEvent;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * An administrator registered a member in the account, under the profile named.
 */
final readonly class MemberRegistered extends AccountEvent
{
    public function __construct(AccountId $accountId, MemberId $by, public MemberId $memberId, public PersonId $profile, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $occurredAt);
    }
}
