<?php

namespace App\Prospecting\Presenter;

use App\Prospecting\Resource\OrganizationPageLinksResource;
use App\Prospecting\Resource\OrganizationPageResource;
use App\Prospecting\Resource\OrganizationSummaryResource;
use App\Shared\ImageResourceFactory;
use App\Shared\Links;
use App\Shared\Resource\LocationResource;
use Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations\FoundOrganization;
use Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations\FoundOrganizationPage;
use Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations\SearchProspectedOrganizationsOutput;

/**
 * A slice of the organizations my organization prospects, each summed up. Built for one call, it keeps the resource
 * (ADR 0085).
 */
final class OrganizationPagePresenter implements SearchProspectedOrganizationsOutput
{
    public private(set) OrganizationPageResource $resource;

    /**
     * @param string $query the search, kept by the link to the next slice
     */
    public function __construct(private readonly ImageResourceFactory $imageResourceFactory, private readonly Links $links, private readonly string $query = '')
    {
    }

    public function present(FoundOrganizationPage $page): void
    {
        $this->resource = new OrganizationPageResource(
            items: array_map($this->summarize(...), $page->organizations),
            links: new OrganizationPageLinksResource(
                $this->links->to('api_organizations_search', array_filter(['q' => $this->query])),
                null === $page->next ? null : $this->links->to('api_organizations_search', array_filter(['q' => $this->query, 'after' => $page->next])),
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
            logo: $this->imageResourceFactory->create($organization->logo?->url),
        );
    }
}
