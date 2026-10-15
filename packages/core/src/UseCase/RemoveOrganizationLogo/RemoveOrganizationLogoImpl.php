<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase\RemoveOrganizationLogo;

use Prospecter\Core\Entity\Organization;
use Prospecter\Core\Exception\UseCase\UseCaseException;
use Prospecter\Core\Gateway\Entity\OrganizationGateway;
use Prospecter\Core\Gateway\Storage;
use Prospecter\Core\Gateway\UserNotifier;
use Prospecter\Core\UseCase\AbstractUseCase;
use Prospecter\Core\UseCase\UseCaseConsumer;

final class RemoveOrganizationLogoImpl extends AbstractUseCase implements RemoveOrganizationLogo
{
    public function __construct(
        private readonly OrganizationGateway $organizations,
        private readonly Storage $storage,
        private readonly UserNotifier $userNotifier,
    ) {
    }

    public function canBeExecutedBy(): array
    {
        return ['ROLE_PROSPECTOR'];
    }

    public function execute(RemoveOrganizationLogoRequest $request, RemoveOrganizationLogoPresenter $presenter, UseCaseConsumer $consumer): mixed
    {
        $organization = $this->organizations->get($request->organizationId);
        $logoUrl = $organization->getLogo()?->url;
        $this->assertBelongsTo($organization, $consumer);

        $organization->removeLogo();
        $this->storage->remove((string) $logoUrl);
        $this->userNotifier->addSuccess('Organization logo was removed !');

        return $presenter->present(new RemoveOrganizationLogoResponse($request->organizationId));
    }

    private function assertBelongsTo(Organization $organization, UseCaseConsumer $consumer): void
    {
        if ($consumer->getOrganizationId() !== $organization->getOwnedBy()?->getId()) {
            throw new UseCaseException(sprintf('Organization "%s" does not belong to your organization.', $organization->getOwnedBy()?->getFullName()));
        }
    }
}
