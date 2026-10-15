<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations;

use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Prospectors;

final readonly class SearchProspectedOrganizationsHandler
{
    public function __construct(private OrganizationRepository $organizations, private Prospectors $prospectors)
    {
    }

    public function __invoke(SearchProspectedOrganizations $query): SearchProspectedOrganizationsResponse
    {
        $prospector = $this->prospectors->get($query->prospectorId);
        $page = $this->organizations->pageProspectedBy($prospector->accountId, $query->query, $query->page, $query->maxByPage);

        return new SearchProspectedOrganizationsResponse(
            currentPage: $page->number,
            total: $page->total,
            totalPages: $page->totalPages,
            organizations: array_map($this->found(...), $page->content),
        );
    }

    private function found(Organization $organization): FoundOrganization
    {
        return new FoundOrganization($organization->id(), $organization->fullName(), $organization->address(), $organization->logo(), $organization->geoPoint());
    }
}
