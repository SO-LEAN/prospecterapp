<?php

namespace App\Presenter;

use Solean\CleanProspecter\UseCase\FindOrganization\FindOrganizationPresenter;
use Solean\CleanProspecter\UseCase\FindOrganization\FindOrganizationResponse;

class FindOrganizationPresenterImpl implements FindOrganizationPresenter
{
    /**
     * @param FindOrganizationResponse $response
     *
     * @return FindOrganizationResponse
     */
    public function present(FindOrganizationResponse $response): FindOrganizationResponse
    {
        return $response;
    }
}
