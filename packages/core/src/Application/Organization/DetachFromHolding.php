<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization;

use Prospecter\Core\Application\UseCaseConsumer;

final class DetachFromHolding extends OrganizationUseCase
{
    public function execute(DetachFromHoldingRequest $request, OrganizationPresenter $presenter, UseCaseConsumer $consumer): mixed
    {
        $organization = $this->prospectedBy($consumer, $request->id);
        $organization->detachFromHolding();

        return $this->saveAndPresent($organization, $presenter);
    }
}
