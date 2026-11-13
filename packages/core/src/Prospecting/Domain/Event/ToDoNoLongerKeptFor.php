<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\Prospecting\Domain\Model\ToDoId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * The account stopped prospecting one of the organizations the to-do was kept for.
 */
final readonly class ToDoNoLongerKeptFor extends ToDoEvent
{
    public function __construct(AccountId $accountId, MemberId $by, ToDoId $toDo, public OrganizationId $organization, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $toDo, $occurredAt);
    }
}
