<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Event;

use Prospecter\Core\PersonalData\Domain\Model\OrganizationId;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * A member collected a person the account prospects, as a contact of these organizations.
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
