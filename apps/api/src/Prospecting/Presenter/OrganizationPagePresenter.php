<?php

declare(strict_types=1);

namespace App\Prospecting\Presenter;

use App\Prospecting\Resource\OrganizationPageLinksResource;
use App\Prospecting\Resource\OrganizationPageResource;
use App\Prospecting\Resource\OrganizationSummaryResource;
use App\Prospecting\Resource\PossibleDuplicateLinksResource;
use App\Prospecting\Resource\PossibleDuplicateResource;
use App\Shared\ImageResourceFactory;
use App\Shared\Links;
use App\Shared\Resource\LocationResource;
use Prospecter\Core\Prospecting\Application\PossibleDuplicate;
use Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations\FoundOrganization;
use Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations\FoundOrganizationPage;
use Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations\SearchProspectedOrganizationsOutput;

/**
 * Presents a slice of the organizations that the member's account prospects, each one summed up. It is built for one
 * call and keeps the resource (ADR 0085).
 */
final class OrganizationPagePresenter implements SearchProspectedOrganizationsOutput
{
    public private(set) OrganizationPageResource $resource;

    /**
     * @param string $query the search, which the link to the next slice keeps
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
            possibleDuplicates: array_map($this->possibleDuplicateOf(...), $organization->possibleDuplicates),
        );
    }

    /**
     * A member states that an organization is not a duplicate from its sheet. The sheet tells whether its state allows
     * it.
     */
    private function possibleDuplicateOf(PossibleDuplicate $duplicate): PossibleDuplicateResource
    {
        return new PossibleDuplicateResource($duplicate->id, $duplicate->fullName, new PossibleDuplicateLinksResource($this->links->to('api_organizations_show', ['id' => $duplicate->id])));
    }
}
