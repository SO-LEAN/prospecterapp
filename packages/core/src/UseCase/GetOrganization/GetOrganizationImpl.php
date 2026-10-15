<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase\GetOrganization;

use Prospecter\Core\Entity\Organization;
use Prospecter\Core\Gateway\Entity\OrganizationGateway;
use Prospecter\Core\Service\ProspectedOrganizations;
use Prospecter\Core\UseCase\AbstractUseCase;
use Prospecter\Core\UseCase\UseCaseConsumer;

final class GetOrganizationImpl extends AbstractUseCase implements GetOrganization
{
    private readonly ProspectedOrganizations $prospectedOrganizations;

    public function __construct(OrganizationGateway $organizations)
    {
        $this->prospectedOrganizations = new ProspectedOrganizations($organizations);
    }

    public function canBeExecutedBy(): array
    {
        return ['ROLE_PROSPECTOR'];
    }

    public function execute(GetOrganizationRequest $request, GetOrganizationPresenter $presenter, UseCaseConsumer $consumer): mixed
    {
        $organization = $this->prospectedOrganizations->visibleTo($consumer->getOrganizationId(), $request->id);

        return $presenter->present($this->buildResponse($organization));
    }

    private function buildResponse(Organization $organization): GetOrganizationResponse
    {
        return new GetOrganizationResponse(
            id: (int) $organization->getId(),
            ownedBy: $organization->getOwnedBy()?->getId(),
            phoneNumber: $organization->getPhoneNumber(),
            email: $organization->getEmail(),
            language: $organization->getLanguage(),
            corporateName: $organization->getCorporateName(),
            form: $organization->getForm(),
            type: $organization->getType(),
            address: $organization->getAddress(),
            geoPoint: $organization->getGeoPoint(),
            observations: $organization->getObservations(),
            logo: $organization->getLogo(),
            holdBy: $organization->getHoldBy()?->getId(),
            stats: $organization->getStats(),
        );
    }
}
