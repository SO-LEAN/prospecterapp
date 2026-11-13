<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\Prospecting\Domain\Model\ToDoId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Something that happened to a to-do.
 */
abstract readonly class ToDoEvent extends DomainEvent
{
    public function __construct(AccountId $accountId, MemberId $by, public ToDoId $toDo, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $occurredAt);
    }

    public function subject(): ToDoId
    {
        return $this->toDo;
    }
}
