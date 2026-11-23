<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Model\ToDoId;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * The to-do's contact to attach was attached to an organization. The to-do is now kept for that organization.
 */
final readonly class ToDoKeptFor extends ToDoEvent
{
    public function __construct(AccountId $accountId, MemberId $by, ToDoId $toDo, public OrganizationId $organization, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $toDo, $occurredAt);
    }
}
