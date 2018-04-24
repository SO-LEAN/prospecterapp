<?php

namespace App\Presenter;

use Solean\CleanProspecter\UseCase\CreateOrganization\CreateOrganizationResponse;
use Solean\CleanProspecter\UseCase\Presenter;

class CreateOrganizationPresenter implements Presenter
{
    /**
     * @param CreateOrganizationResponse $response
     *
     * @return mixed
     */
    public function present($response)
    {
        return $response;
    }
}
