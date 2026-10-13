<?php

declare( strict_types = 1 );

namespace Prospecter\Core\UseCase\UpdateMyAccountInformation;

use Prospecter\Core\UseCase\UseCaseConsumer;

interface UpdateMyAccountInformation
{
    public function execute(UpdateMyAccountInformationRequest $request, UpdateMyAccountInformationPresenter $presenter, UseCaseConsumer $consumer): ?object;
}
