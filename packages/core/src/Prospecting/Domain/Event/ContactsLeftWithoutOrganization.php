<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The organization had these contacts. No organization of the account names them any more, so they must not stay (ADR
 * 0042).
 */
final readonly class ContactsLeftWithoutOrganization extends OrganizationEvent
{
    /**
     * @param list<ContactId> $contacts
     */
    public function __construct(AccountId $accountId, MemberId $by, OrganizationId $organization, public array $contacts, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $organization, $occurredAt);
    }
}
