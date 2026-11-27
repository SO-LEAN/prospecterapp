<?php

declare(strict_types=1);

namespace App\Connectors\AntiCorruption;

use Prospecter\Core\Connectors\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Connectors\Domain\Model\OrganizationId;
use Prospecter\Core\Connectors\Domain\Port\OrganizationAddresses;
use Prospecter\Core\Prospecting\Contract\Service\WhichEmailsHaveTheseOrganizations\KeptEmailsOfOrganizations;
use Prospecter\Core\Prospecting\Contract\Service\WhichEmailsHaveTheseOrganizations\WhichEmailsHaveTheseOrganizations;
use Prospecter\Core\Prospecting\Contract\Service\WhichEmailsHaveTheseOrganizations\WhichEmailsHaveTheseOrganizationsService;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Anticorruption layer (ADR 0045, 0089, 0223). Connectors gets an organization's address from Prospecting. It
 * asks about a set of one. It searches the mailbox by the address and never keeps it.
 */
final readonly class ProspectingOrganizationAddresses implements OrganizationAddresses
{
    public function __construct(private WhichEmailsHaveTheseOrganizationsService $whichEmailsHaveTheseOrganizationsService)
    {
    }

    public function of(AccountId $accountId, OrganizationId $organization): ?Email
    {
        $kept = new KeptEmailsOfOrganizations();
        ($this->whichEmailsHaveTheseOrganizationsService)(new WhichEmailsHaveTheseOrganizations([$organization->value], $accountId->value), $kept);

        if (!array_key_exists($organization->value, $kept->emails)) {
            throw UnknownOrganization::withId($organization);
        }

        $address = $kept->emails[$organization->value];

        return null === $address ? null : new Email($address);
    }
}
