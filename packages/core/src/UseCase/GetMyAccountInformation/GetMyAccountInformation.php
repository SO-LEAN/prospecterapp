<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase\GetMyAccountInformation;

use Prospecter\Core\UseCase\UseCaseConsumer;

interface GetMyAccountInformation
{
    public function execute(GetMyAccountInformationRequest $request, GetMyAccountInformationPresenter $presenter, UseCaseConsumer $consumer): mixed;
}
