<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * These contacts of the organization are no contact of any organization of the account any more: they must not
 * stay (ADR 0042).
 */
final readonly class ContactsLeftWithoutOrganization extends OrganizationEvent
{
    /**
     * @param list<PersonId> $contacts
     */
    public function __construct(AccountId $accountId, MemberId $by, OrganizationId $organization, public array $contacts, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $organization, $occurredAt);
    }
}
