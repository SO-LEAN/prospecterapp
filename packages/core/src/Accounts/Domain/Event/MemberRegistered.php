<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Event;

use Prospecter\Core\Accounts\Domain\Model\ProfileId;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * An administrator registered a member in the account, under the profile the event names.
 */
final readonly class MemberRegistered extends AccountEvent
{
    public function __construct(AccountId $accountId, MemberId $by, public MemberId $memberId, public ProfileId $profile, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $occurredAt);
    }
}
