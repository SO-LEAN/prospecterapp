<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * Something that happened to an organization the account prospects.
 */
abstract readonly class OrganizationEvent extends DomainEvent
{
    public function __construct(AccountId $accountId, MemberId $by, public OrganizationId $organization, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $occurredAt);
    }

    public function subject(): OrganizationId
    {
        return $this->organization;
    }
}
