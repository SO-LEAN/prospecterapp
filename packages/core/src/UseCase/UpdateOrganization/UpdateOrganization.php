<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase\UpdateOrganization;

interface UpdateOrganization
{
    public function execute(UpdateOrganizationRequest $request, UpdateOrganizationPresenter $presenter): mixed;
}
