<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase\FindMyOwnOrganizations;

use Prospecter\Core\Entity\Organization;
use Prospecter\Core\Gateway\Entity\OrganizationGateway;
use Prospecter\Core\Gateway\Entity\PageRequest;
use Prospecter\Core\UseCase\AbstractUseCase;
use Prospecter\Core\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsResponse\Organization as FoundOrganization;
use Prospecter\Core\UseCase\UseCaseConsumer;

final class FindMyOwnOrganizationsImpl extends AbstractUseCase implements FindMyOwnOrganizations
{
    public function __construct(private readonly OrganizationGateway $organizations)
    {
    }

    public function canBeExecutedBy(): array
    {
        return ['ROLE_PROSPECTOR'];
    }

    public function execute(FindMyOwnOrganizationsRequest $request, FindMyOwnOrganizationsPresenter $presenter, UseCaseConsumer $consumer): mixed
    {
        $page = $this->organizations->findPageByQuery(new PageRequest($request->page, $request->query, $request->maxByPage, ['ownedBy' => $consumer->getOrganizationId()]));

        return $presenter->present(new FindMyOwnOrganizationsResponse(
            currentPage: $page->number,
            total: $page->total,
            totalPages: $page->totalPages,
            organizations: array_map($this->found(...), $page->content),
        ));
    }

    private function found(Organization $organization): FoundOrganization
    {
        return new FoundOrganization(
            id: (int) $organization->getId(),
            fullName: $organization->getFullName(),
            address: $organization->getAddress(),
            logo: $organization->getLogo(),
            geoPoint: $organization->getGeoPoint(),
        );
    }
}
