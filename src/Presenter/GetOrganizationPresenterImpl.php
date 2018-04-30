<?php

namespace App\Presenter;

use Solean\CleanProspecter\UseCase\GetOrganization\GetOrganizationResponse;
use Solean\CleanProspecter\UseCase\GetOrganization\GetOrganizationPresenter;

class GetOrganizationPresenterImpl implements GetOrganizationPresenter
{
    /**
     * @param GetOrganizationResponse $response
     *
     * @return array
     */
    public function present(GetOrganizationResponse $response): GetOrganizationResponse
    {
      return $response;
    }
}
