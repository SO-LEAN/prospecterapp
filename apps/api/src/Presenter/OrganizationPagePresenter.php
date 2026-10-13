<?php

namespace App\Presenter;

use App\Api\ImageResourceFactory;
use App\Api\Resource\LocationResource;
use App\Api\Resource\OrganizationPageResource;
use App\Api\Resource\OrganizationSummaryResource;
use Prospecter\Core\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsPresenter;
use Prospecter\Core\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsResponse;
use Prospecter\Core\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsResponse\Organization;

final readonly class OrganizationPagePresenter implements FindMyOwnOrganizationsPresenter
{
    public function __construct(private ImageResourceFactory $images)
    {
    }

    public function present(FindMyOwnOrganizationsResponse $response): OrganizationPageResource
    {
        return new OrganizationPageResource(
            items: array_values(array_map($this->summarize(...), $response->getOrganizations())),
            page: $response->getCurrentPage(),
            totalPages: $response->getTotalPages(),
            totalItems: $response->getTotal(),
        );
    }

    private function summarize(Organization $organization): OrganizationSummaryResource
    {
        return new OrganizationSummaryResource(
            id: (int) $organization->getId(),
            name: $organization->getFullName(),
            postalCode: $organization->getPostalCode(),
            city: $organization->getCity(),
            country: $organization->getCountry(),
            location: LocationResource::fromCoordinates($organization->getLatitude(), $organization->getLongitude()),
            logo: $this->images->create($organization->getLogo()),
        );
    }
}
