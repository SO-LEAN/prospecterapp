<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A person the account prospects was collected, as a contact of these organizations.
 */
final readonly class ContactCollected extends PersonEvent
{
    /**
     * @param list<OrganizationId> $organizations
     */
    public function __construct(AccountId $accountId, MemberId $by, PersonId $person, public array $organizations, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $person, $occurredAt);
    }
}
