<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase\GetOrganization;

interface GetOrganization
{
    public function execute(GetOrganizationRequest $request, GetOrganizationPresenter $presenter): mixed;
}
