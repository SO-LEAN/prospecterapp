<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The organization had these contacts. No organization of the account names them any more, so they must not stay (ADR
 * 0042).
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
