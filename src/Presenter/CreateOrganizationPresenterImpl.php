<?php

namespace App\Presenter;

use Solean\CleanProspecter\UseCase\CreateOrganization\CreateOrganizationPresenter;
use Solean\CleanProspecter\UseCase\CreateOrganization\CreateOrganizationResponse;

class CreateOrganizationPresenterImpl implements CreateOrganizationPresenter
{
    public function present(CreateOrganizationResponse $response): CreateOrganizationResponse
    {
        return $response;
    }
}
