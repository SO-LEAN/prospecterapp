<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A contact a member collects: the person, their card, and the organizations where they work, which the account
 * prospects (ADR 0042).
 */
final readonly class NewContact
{
    /**
     * @param list<OrganizationId> $organizations
     */
    public function __construct(
        public PersonId $id,
        public ContactCard $card,
        public array $organizations,
    ) {
    }
}
