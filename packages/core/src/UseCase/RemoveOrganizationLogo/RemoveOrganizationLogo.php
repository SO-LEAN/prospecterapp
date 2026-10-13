<?php

namespace Prospecter\Core\UseCase\RemoveOrganizationLogo;

use Prospecter\Core\UseCase\UseCaseConsumer;

interface RemoveOrganizationLogo
{
    public function execute(RemoveOrganizationLogoRequest $request, RemoveOrganizationLogoPresenter $presenter, UseCaseConsumer $consumer);
}
