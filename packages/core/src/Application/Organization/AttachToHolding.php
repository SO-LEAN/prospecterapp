<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization;

use Prospecter\Core\Application\UseCaseConsumer;

final class AttachToHolding extends OrganizationUseCase
{
    public function execute(AttachToHoldingRequest $request, OrganizationPresenter $presenter, UseCaseConsumer $consumer): mixed
    {
        $organization = $this->prospectedBy($consumer, $request->id);
        $holding = $this->prospectedOrganizations->holdingProspectedBy($consumer->getOrganizationId(), $request->holdingId);
        $organization->attachToHolding($holding->id());

        return $this->saveAndPresent($organization, $presenter);
    }
}
