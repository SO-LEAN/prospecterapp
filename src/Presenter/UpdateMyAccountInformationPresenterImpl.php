<?php

namespace App\Presenter;

use Solean\CleanProspecter\UseCase\UpdateMyAccountInformation\UpdateMyAccountInformationPresenter;
use Solean\CleanProspecter\UseCase\UpdateMyAccountInformation\UpdateMyAccountInformationResponse;

class UpdateMyAccountInformationPresenterImpl implements UpdateMyAccountInformationPresenter
{
    public function present(UpdateMyAccountInformationResponse $response): UpdateMyAccountInformationResponse
    {
        return $response;
    }
}
