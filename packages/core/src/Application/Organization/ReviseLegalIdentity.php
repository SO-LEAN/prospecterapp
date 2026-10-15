<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization;

use Prospecter\Core\Application\UseCaseConsumer;

final class ReviseLegalIdentity extends OrganizationUseCase
{
    public function execute(ReviseLegalIdentityRequest $request, OrganizationPresenter $presenter, UseCaseConsumer $consumer): mixed
    {
        $organization = $this->prospectedBy($consumer, $request->id);
        $organization->reviseLegalIdentity($request->legalIdentity);

        return $this->saveAndPresent($organization, $presenter);
    }
}
