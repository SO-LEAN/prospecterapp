<?php

namespace App\Prospecting\Presenter;

use App\Prospecting\Resource\LocationResource;
use App\Prospecting\Resource\OrganizationPageResource;
use App\Prospecting\Resource\OrganizationSummaryResource;
use App\Shared\ImageResourceFactory;
use Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations\FoundOrganization;
use Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations\SearchProspectedOrganizationsResponse;

final readonly class OrganizationPagePresenter
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
            id: $organization->id,
            name: $organization->fullName,
            postalCode: $organization->postalCode,
            city: $organization->city,
            country: $organization->country,
            location: LocationResource::orNone($organization->latitude, $organization->longitude),
            logo: $this->images->create($organization->logo?->url),
        );
    }
}
