<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization;

use Prospecter\Core\Application\UseCase;
use Prospecter\Core\Application\UseCaseConsumer;
use Prospecter\Core\Domain\Organization\Organization;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\User\Role;

final class SearchProspectedOrganizations extends UseCase
{
    public function __construct(private readonly OrganizationRepository $organizations)
    {
    }

    public function canBeExecutedBy(): array
    {
        return [Role::Prospector];
    }

    public function execute(SearchProspectedOrganizationsRequest $request, SearchProspectedOrganizationsPresenter $presenter, UseCaseConsumer $consumer): mixed
    {
        $page = $this->organizations->pageProspectedBy($consumer->getOrganizationId(), $request->query, $request->page, $request->maxByPage);

        return $presenter->present(new SearchProspectedOrganizationsResponse(
            currentPage: $page->number,
            total: $page->total,
            totalPages: $page->totalPages,
            organizations: array_map($this->found(...), $page->content),
        ));
    }

    private function found(Organization $organization): FoundOrganization
    {
        return new FoundOrganization($organization->id(), $organization->fullName(), $organization->address(), $organization->logo(), $organization->geoPoint());
    }
}
