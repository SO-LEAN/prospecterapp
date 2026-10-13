<?php

namespace App\Presenter;

use Prospecter\Core\UseCase\UpdateMyAccountInformation\UpdateMyAccountInformationPresenter;
use Prospecter\Core\UseCase\UpdateMyAccountInformation\UpdateMyAccountInformationResponse;

class UpdateMyAccountInformationPresenterImpl implements UpdateMyAccountInformationPresenter
{
    public function present(UpdateMyAccountInformationResponse $response): UpdateMyAccountInformationResponse
    {
        return $response;
    }
}
