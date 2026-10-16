<?php

namespace App\Prospecting\Presenter;

use App\Prospecting\Resource\LocationResource;
use App\Prospecting\Resource\OrganizationPageLinksResource;
use App\Prospecting\Resource\OrganizationPageResource;
use App\Prospecting\Resource\OrganizationSummaryResource;
use App\Shared\ImageResourceFactory;
use App\Shared\Links;
use Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations\FoundOrganization;
use Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations\SearchProspectedOrganizationsResponse;

final readonly class OrganizationPagePresenter
{
    public function __construct(private ImageResourceFactory $images, private Links $links)
    {
    }

    /**
     * @param string $query the search, kept by the link to the next slice
     */
    public function present(SearchProspectedOrganizationsResponse $response, string $query = ''): OrganizationPageResource
    {
        return new OrganizationPageResource(
            items: array_map($this->summarize(...), $response->organizations),
            links: new OrganizationPageLinksResource(
                $this->links->to('api_organizations_search', array_filter(['q' => $query])),
                null === $response->next ? null : $this->links->to('api_organizations_search', array_filter(['q' => $query, 'after' => $response->next])),
            ),
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
