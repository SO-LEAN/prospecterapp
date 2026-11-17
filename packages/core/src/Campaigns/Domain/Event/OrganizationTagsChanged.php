<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * A member put tags on an organization, or took one off.
 */
final readonly class OrganizationTagsChanged extends DomainEvent
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
