<?php

declare( strict_types = 1 );

namespace Prospecter\Core\UseCase\GetMyAccountInformation;

interface GetMyAccountInformationPresenter
{
    public function present(GetMyAccountInformationResponse $response);
}
