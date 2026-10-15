<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase\RemoveOrganizationLogo;

use Prospecter\Core\Exception\UseCase\UseCaseException;
use Prospecter\Core\Gateway\Entity\OrganizationGateway;
use Prospecter\Core\Gateway\Storage;
use Prospecter\Core\Gateway\UserNotifier;
use Prospecter\Core\Service\ProspectedOrganizations;
use Prospecter\Core\UseCase\AbstractUseCase;
use Prospecter\Core\UseCase\UseCaseConsumer;

final class RemoveOrganizationLogoImpl extends AbstractUseCase implements RemoveOrganizationLogo
{
    private readonly ProspectedOrganizations $prospectedOrganizations;

    public function __construct(
        private readonly OrganizationGateway $organizations,
        private readonly Storage $storage,
        private readonly UserNotifier $userNotifier,
    ) {
        $this->prospectedOrganizations = new ProspectedOrganizations($organizations);
    }

    public function canBeExecutedBy(): array
    {
        return ['ROLE_PROSPECTOR'];
    }

    public function execute(RemoveOrganizationLogoRequest $request, RemoveOrganizationLogoPresenter $presenter, UseCaseConsumer $consumer): mixed
    {
        $organization = $this->prospectedOrganizations->prospectedBy($consumer->getOrganizationId(), $request->organizationId);
        $logo = $organization->getLogo() ?? throw new UseCaseException('There is no logo to remove', ['logo' => 'There is no logo to remove']);

        $organization->removeLogo();
        $this->organizations->update($request->organizationId, $organization);
        $this->storage->remove($logo->url);
        $this->userNotifier->addSuccess('Organization logo was removed !');

        return $presenter->present(new RemoveOrganizationLogoResponse($request->organizationId));
    }
}
