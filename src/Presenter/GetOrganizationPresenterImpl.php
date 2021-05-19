<?php

namespace App\Presenter;

use Solean\CleanProspecter\UseCase\GetOrganization\GetOrganizationPresenter;
use Solean\CleanProspecter\UseCase\GetOrganization\GetOrganizationResponse;

class GetOrganizationPresenterImpl implements GetOrganizationPresenter
{
    public function present(GetOrganizationResponse $response): GetOrganizationResponse
    {
        return $response;
    }
}
