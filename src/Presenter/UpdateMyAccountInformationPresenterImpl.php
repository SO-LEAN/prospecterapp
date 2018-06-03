<?php

namespace App\Presenter;

use Solean\CleanProspecter\UseCase\UpdateMyAccountInformation\UpdateMyAccountInformationResponse;
use Solean\CleanProspecter\UseCase\UpdateMyAccountInformation\UpdateMyAccountInformationPresenter;

class UpdateMyAccountInformationPresenterImpl implements UpdateMyAccountInformationPresenter
{
    /**
     * @param UpdateMyAccountInformationResponse $response
     *
     * @return UpdateMyAccountInformationResponse
     */
    public function present(UpdateMyAccountInformationResponse $response): UpdateMyAccountInformationResponse
    {
        return $response;
    }
}
