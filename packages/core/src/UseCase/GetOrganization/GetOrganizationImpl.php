<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase\GetOrganization;

use Prospecter\Core\Entity\Organization;
use Prospecter\Core\Exception\Gateway;
use Prospecter\Core\Exception\UseCase\NotFoundException;
use Prospecter\Core\Gateway\Entity\OrganizationGateway;
use Prospecter\Core\UseCase\AbstractUseCase;

final class GetOrganizationImpl extends AbstractUseCase implements GetOrganization
{
    public function __construct(private readonly OrganizationGateway $organizations)
    {
    }

    public function canBeExecutedBy(): array
    {
        return ['ROLE_PROSPECTOR'];
    }

    public function execute(GetOrganizationRequest $request, GetOrganizationPresenter $presenter): mixed
    {
        return $presenter->present($this->buildResponse($this->organization($request->id)));
    }

    private function organization(int $id): Organization
    {
        try {
            return $this->organizations->get($id);
        } catch (Gateway\NotFoundException $e) {
            throw new NotFoundException(sprintf('Organization with #ID %d not found', $id), ['id' => 'Organization not found'], $e);
        }
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
