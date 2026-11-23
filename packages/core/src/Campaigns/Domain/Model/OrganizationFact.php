<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * An organization as Prospecting publishes it. It says whether the account prospects it and which contacts it names.
 * These contacts inherit its tags. It also says where the organization is, which sets its geographic tags (ADR 0142).
 */
final readonly class OrganizationFact implements Fact
{
    /**
     * @param list<ContactId> $contacts none once absent
     */
    public function __construct(
        public AccountId $accountId,
        public OrganizationId $id,
        public int $revision,
        public bool $present,
        public array $contacts,
        public Locality $locality,
    ) {
    }
}
