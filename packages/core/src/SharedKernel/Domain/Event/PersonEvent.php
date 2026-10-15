<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Something that happened to the personal data of a person.
 */
abstract readonly class PersonEvent extends DomainEvent
{
    public function __construct(AccountId $accountId, MemberId $by, public PersonId $person, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $occurredAt);
    }

    public function subject(): PersonId
    {
        return $this->person;
    }
}
