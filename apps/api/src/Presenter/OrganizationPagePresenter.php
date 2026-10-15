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
            items: array_map($this->summarize(...), $response->organizations),
            page: $response->currentPage,
            totalPages: $response->totalPages,
            totalItems: $response->total,
        );
    }

    private function summarize(Organization $organization): OrganizationSummaryResource
    {
        return new OrganizationSummaryResource(
            id: $organization->id,
            name: $organization->fullName,
            postalCode: $organization->address?->postalCode,
            city: $organization->address?->city,
            country: $organization->address?->country,
            location: LocationResource::from($organization->geoPoint),
            logo: $this->images->create($organization->logo?->url),
        );
    }
}
