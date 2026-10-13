<?php

namespace App\Presenter;

use Prospecter\Core\UseCase\CreateOrganization\CreateOrganizationPresenter;
use Prospecter\Core\UseCase\CreateOrganization\CreateOrganizationResponse;

class CreateOrganizationPresenterImpl implements CreateOrganizationPresenter
{
    public function present(CreateOrganizationResponse $response): CreateOrganizationResponse
    {
        return $response;
    }
}
