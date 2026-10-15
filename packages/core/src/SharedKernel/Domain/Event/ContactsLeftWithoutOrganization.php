<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * These contacts are no contact of any organization of the account any more: they must not stay (ADR 0042).
 */
final readonly class ContactsLeftWithoutOrganization
{
    /**
     * @param list<PersonId> $contacts
     */
    public function __construct(
        public AccountId $accountId,
        public MemberId $leftBy,
        public array $contacts,
        public \DateTimeImmutable $occurredAt,
    ) {
    }
}
