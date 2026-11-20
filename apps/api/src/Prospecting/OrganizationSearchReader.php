<?php

namespace App\Prospecting;

use App\Prospecting\Presenter\OrganizationPagePresenter;
use App\Prospecting\Resource\OrganizationPageResource;
use App\Shared\ImageResourceFactory;
use App\Shared\Links;
use Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations\SearchProspectedOrganizations;
use Prospecter\Core\SharedKernel\Application\QueryHandler;

/**
 * A slice of the organizations the account of a member prospects, by the start of their corporate name or their
 * email, as its endpoint and its tool answer it.
 */
final readonly class OrganizationSearchReader
{
    public function __construct(
        private QueryHandler $searchProspectedOrganizations,
        private ImageResourceFactory $imageResourceFactory,
        private Links $links,
    ) {
    }

    public function search(string $memberId, string $query, ?string $after): OrganizationPageResource
    {
        $presenter = new OrganizationPagePresenter($this->imageResourceFactory, $this->links, $query);
        ($this->searchProspectedOrganizations)(new SearchProspectedOrganizations($memberId, $query, $after), $presenter);

        return $presenter->resource;
    }
}
