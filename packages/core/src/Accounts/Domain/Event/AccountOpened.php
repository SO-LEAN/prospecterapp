<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Event;

use Prospecter\Core\Accounts\Domain\Model\ProfileId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * An organization opened its account. Its founder becomes its first member, with the profile the event names.
 */
final readonly class AccountOpened extends AccountEvent
{
    public function __construct(AccountId $accountId, MemberId $by, public ProfileId $founderProfile, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $occurredAt);
    }
}
