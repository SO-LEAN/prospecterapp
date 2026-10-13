<?php

namespace App\Presenter;

use Prospecter\Core\UseCase\GetOrganization\GetOrganizationPresenter;
use Prospecter\Core\UseCase\GetOrganization\GetOrganizationResponse;

class GetOrganizationPresenterImpl implements GetOrganizationPresenter
{
    public function present(GetOrganizationResponse $response): GetOrganizationResponse
    {
        return $response;
    }
}
