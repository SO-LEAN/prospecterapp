<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization;

use Prospecter\Core\Application\UseCaseConsumer;

final class ChangeContactDetails extends OrganizationUseCase
{
    public function execute(ChangeContactDetailsRequest $request, OrganizationPresenter $presenter, UseCaseConsumer $consumer): mixed
    {
        $organization = $this->prospectedBy($consumer, $request->id);
        $organization->changeContactDetails($request->contactDetails);

        return $this->saveAndPresent($organization, $presenter);
    }
}
