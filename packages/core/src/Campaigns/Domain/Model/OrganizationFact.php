<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * An organization as Prospecting publishes it: whether the account prospects it, and the contacts it names, who
 * inherit its tags.
 */
final readonly class OrganizationFact implements Fact
{
    /**
     * @param list<PersonId> $contacts none once absent
     */
    public function __construct(
        public AccountId $accountId,
        public OrganizationId $id,
        public int $revision,
        public bool $present,
        public array $contacts,
    ) {
    }
}
