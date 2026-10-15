<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization;

use Prospecter\Core\Application\UseCaseConsumer;

final class WriteObservations extends OrganizationUseCase
{
    public function execute(WriteObservationsRequest $request, OrganizationPresenter $presenter, UseCaseConsumer $consumer): mixed
    {
        $organization = $this->prospectedBy($consumer, $request->id);
        $organization->writeObservations($request->observations);

        return $this->saveAndPresent($organization, $presenter);
    }
}
