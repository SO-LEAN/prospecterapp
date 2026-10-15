<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase\RemoveOrganizationLogo;

use Prospecter\Core\UseCase\UseCaseConsumer;

interface RemoveOrganizationLogo
{
    public function execute(RemoveOrganizationLogoRequest $request, RemoveOrganizationLogoPresenter $presenter, UseCaseConsumer $consumer): mixed;
}
