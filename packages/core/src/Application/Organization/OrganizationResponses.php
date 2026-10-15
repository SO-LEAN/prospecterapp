<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization;

use Prospecter\Core\Domain\Organization\Organization;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\User\User;

final readonly class OrganizationResponses
{
    public function __construct(private OrganizationRepository $organizations)
    {
    }

    public function of(Organization $organization, User $viewer): OrganizationResponse
    {
        return new OrganizationResponse(
            id: $organization->id(),
            ownerId: $organization->ownerId(),
            legalIdentity: $organization->legalIdentity(),
            fullName: $organization->fullName(),
            contactDetails: $organization->contactDetails(),
            address: $organization->address(),
            geoPoint: $organization->geoPoint(),
            observations: $organization->observations(),
            logo: $organization->logo(),
            holdingId: $organization->holdingId(),
            activeOrganizations: $this->organizations->countProspectedBy($organization->id()),
            possibleIntents: $organization->possibleIntentsOf($viewer),
        );
    }
}
