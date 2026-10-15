<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase\UpdateOrganization;

use Prospecter\Core\UseCase\UseCaseConsumer;

interface UpdateOrganization
{
    public function execute(UpdateOrganizationRequest $request, UpdateOrganizationPresenter $presenter, UseCaseConsumer $consumer): mixed;
}
