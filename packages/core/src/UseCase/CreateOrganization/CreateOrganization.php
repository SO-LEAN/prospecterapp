<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase\CreateOrganization;

interface CreateOrganization
{
    public function execute(CreateOrganizationRequest $request, CreateOrganizationPresenter $presenter): mixed;
}
