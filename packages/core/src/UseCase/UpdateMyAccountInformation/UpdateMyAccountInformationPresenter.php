<?php

declare( strict_types = 1 );

namespace Prospecter\Core\UseCase\UpdateMyAccountInformation;

interface UpdateMyAccountInformationPresenter
{
    public function present(UpdateMyAccountInformationResponse $response);
}
