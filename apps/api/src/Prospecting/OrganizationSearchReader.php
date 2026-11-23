<?php

declare(strict_types=1);

namespace App\Prospecting;

use App\Prospecting\Presenter\OrganizationPagePresenter;
use App\Prospecting\Resource\OrganizationPageResource;
use App\Shared\ImageResourceFactory;
use App\Shared\Links;
use Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations\SearchProspectedOrganizations;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

/**
 * Reads a slice of the organizations a member's account prospects, found by the start of their corporate name or of
 * their email. The endpoint and the MCP tool answer with it.
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
        $this->searchProspectedOrganizations->__invoke(new SearchProspectedOrganizations($memberId, $query, $after), $presenter);

        return $presenter->resource;
    }
}
