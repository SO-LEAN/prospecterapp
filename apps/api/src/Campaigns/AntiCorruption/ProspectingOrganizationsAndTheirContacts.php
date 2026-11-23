<?php

declare(strict_types=1);

namespace App\Campaigns\AntiCorruption;

use Prospecter\Core\Campaigns\Domain\Model\Locality;
use Prospecter\Core\Campaigns\Domain\Model\OrganizationFact;
use Prospecter\Core\Campaigns\Domain\Model\OrganizationId;
use Prospecter\Core\Campaigns\Domain\Port\OrganizationsAndTheirContacts;
use Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts\KeptOrganizationFacts;
use Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts\ListOrganizationFacts;
use Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts\ListOrganizationFactsService;
use Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts\OrganizationFactsResponse;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Anticorruption layer (ADR 0045, 0089, 0141): the facts that Prospecting gives again, one slice after another, in
 * Campaigns' words.
 */
final readonly class ProspectingOrganizationsAndTheirContacts implements OrganizationsAndTheirContacts
{
    private const int SLICE = 100;

    public function __construct(private ListOrganizationFactsService $listOrganizationFactsService)
    {
    }

    public function of(AccountId $accountId): iterable
    {
        $after = null;

        do {
            $kept = new KeptOrganizationFacts();
            ($this->listOrganizationFactsService)(new ListOrganizationFacts($accountId->value, $after, self::SLICE), $kept);

            yield from array_map(static fn (OrganizationFactsResponse $facts): OrganizationFact => new OrganizationFact(
                $accountId,
                new OrganizationId($facts->organizationId),
                $facts->revision,
                $facts->present,
                array_map(static fn (string $contact): PersonId => new PersonId($contact), $facts->contacts),
                new Locality($facts->municipalityCode, $facts->country),
            ), $kept->facts);

            $after = $kept->next;
        } while (null !== $after);
    }
}
