<?php

namespace App\Presenter;

use Solean\CleanProspecter\UseCase\CreateOrganization\CreateOrganizationResponse;
use Solean\CleanProspecter\UseCase\CreateOrganization\CreateOrganizationPresenter;

class CreateOrganizationPresenterImpl implements CreateOrganizationPresenter
{
    /**
     * @param CreateOrganizationResponse $response
     *
     * @return CreateOrganizationResponse
     */
    public function present(CreateOrganizationResponse $response): CreateOrganizationResponse
    {
        return $response;
    }
}
