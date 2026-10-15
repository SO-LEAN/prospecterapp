<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization\SearchProspectedOrganizations;

use Prospecter\Core\Domain\Organization\Organization;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\User\UserRepository;

final readonly class SearchProspectedOrganizationsHandler
{
    public function __construct(private OrganizationRepository $organizations, private UserRepository $users)
    {
    }

    public function __invoke(SearchProspectedOrganizations $query): SearchProspectedOrganizationsResponse
    {
        $prospector = $this->users->get($query->prospectorId);
        $page = $this->organizations->pageProspectedBy($prospector->organizationId(), $query->query, $query->page, $query->maxByPage);

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
