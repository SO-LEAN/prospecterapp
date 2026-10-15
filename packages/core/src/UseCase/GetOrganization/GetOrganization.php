<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase\GetOrganization;

use Prospecter\Core\UseCase\UseCaseConsumer;

interface GetOrganization
{
    public function execute(GetOrganizationRequest $request, GetOrganizationPresenter $presenter, UseCaseConsumer $consumer): mixed;
}
