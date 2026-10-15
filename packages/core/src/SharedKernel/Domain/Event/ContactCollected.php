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
final readonly class ContactCollected
{
    /**
     * @param list<OrganizationId> $organizations
     */
    public function __construct(
        public AccountId $accountId,
        public MemberId $collectedBy,
        public PersonId $contact,
        public array $organizations,
        public \DateTimeImmutable $occurredAt,
    ) {
    }
}
