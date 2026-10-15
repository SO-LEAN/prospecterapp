<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization;

use Prospecter\Core\Application\Service\ProspectedOrganizations;
use Prospecter\Core\Application\UseCase;
use Prospecter\Core\Application\UseCaseConsumer;
use Prospecter\Core\Domain\Organization\Organization;
use Prospecter\Core\Domain\Organization\OrganizationId;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\User\Role;

/**
 * An intention of a prospector on one of the organizations its organization prospects: load it, change it
 * through the aggregate, save it, present it.
 */
abstract class OrganizationUseCase extends UseCase
{
    protected readonly ProspectedOrganizations $prospectedOrganizations;
    private readonly OrganizationResponses $responses;

    public function __construct(protected readonly OrganizationRepository $organizations)
    {
        $this->prospectedOrganizations = new ProspectedOrganizations($organizations);
        $this->responses = new OrganizationResponses($organizations);
    }

    public function canBeExecutedBy(): array
    {
        return [Role::Prospector];
    }

    protected function prospectedBy(UseCaseConsumer $consumer, OrganizationId $id): Organization
    {
        return $this->prospectedOrganizations->prospectedBy($consumer->getOrganizationId(), $id);
    }

    protected function saveAndPresent(Organization $organization, OrganizationPresenter $presenter): mixed
    {
        $this->organizations->save($organization);

        return $this->present($organization, $presenter);
    }

    protected function present(Organization $organization, OrganizationPresenter $presenter): mixed
    {
        return $presenter->present($this->responses->of($organization));
    }
}
