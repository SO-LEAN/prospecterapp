<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase\FindMyOwnOrganizations;

use Prospecter\Core\UseCase\UseCaseConsumer;

interface FindMyOwnOrganizations
{
    public function execute(FindMyOwnOrganizationsRequest $request, FindMyOwnOrganizationsPresenter $presenter, UseCaseConsumer $consumer): mixed;
}
