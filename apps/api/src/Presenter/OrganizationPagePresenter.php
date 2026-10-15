<?php

namespace App\Presenter;

use App\Api\ImageResourceFactory;
use App\Api\Resource\LocationResource;
use App\Api\Resource\OrganizationPageResource;
use App\Api\Resource\OrganizationSummaryResource;
use Prospecter\Core\Application\Organization\SearchProspectedOrganizations\FoundOrganization;
use Prospecter\Core\Application\Organization\SearchProspectedOrganizations\SearchProspectedOrganizationsPresenter;
use Prospecter\Core\Application\Organization\SearchProspectedOrganizations\SearchProspectedOrganizationsResponse;

final readonly class OrganizationPagePresenter implements SearchProspectedOrganizationsPresenter
{
    public function __construct(private ImageResourceFactory $images)
    {
    }

    public function present(SearchProspectedOrganizationsResponse $response): OrganizationPageResource
    {
        return new OrganizationPageResource(
            items: array_map($this->summarize(...), $response->organizations),
            page: $response->currentPage,
            totalPages: $response->totalPages,
            totalItems: $response->total,
        );
    }

    private function summarize(FoundOrganization $organization): OrganizationSummaryResource
    {
        return new OrganizationSummaryResource(
            id: $organization->id->value,
            name: $organization->fullName,
            postalCode: $organization->address?->postalCode,
            city: $organization->address?->city,
            country: $organization->address?->country,
            location: LocationResource::from($organization->geoPoint),
            logo: $this->images->create($organization->logo?->url),
        );
    }
}
