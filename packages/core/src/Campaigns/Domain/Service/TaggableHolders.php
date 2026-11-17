<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Service;

use Prospecter\Core\Campaigns\Domain\Exception\UnknownContact;
use Prospecter\Core\Campaigns\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Campaigns\Domain\Model\Holder;
use Prospecter\Core\Campaigns\Domain\Model\HolderKind;
use Prospecter\Core\Campaigns\Domain\Port\TaggableContacts;
use Prospecter\Core\Campaigns\Domain\Port\TaggableOrganizations;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Domain service (ADR 0039): an account tags only the organizations it prospects and its contacts, as Prospecting and
 * PersonalData know them; anything else is unknown to it.
 */
final readonly class TaggableHolders
{
    public function __construct(
        private TaggableOrganizations $organizations,
        private TaggableContacts $contacts,
    ) {
    }

    /**
     * @throws UnknownOrganization
     * @throws UnknownContact
     */
    public function ensureKnownBy(AccountId $accountId, Holder $holder): void
    {
        match ($holder->kind) {
            HolderKind::Organization => $this->organizations->isProspectedBy(new OrganizationId($holder->id->value), $accountId) ?: throw UnknownOrganization::identifiedBy($holder->id),
            HolderKind::Contact => $this->contacts->isAContactOf(new PersonId($holder->id->value), $accountId) ?: throw UnknownContact::identifiedBy($holder->id),
        };
    }
}
