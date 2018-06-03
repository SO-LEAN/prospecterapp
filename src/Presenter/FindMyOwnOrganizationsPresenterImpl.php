<?php

namespace App\Presenter;

use Solean\CleanProspecter\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsResponse;
use Solean\CleanProspecter\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsPresenter;

class FindMyOwnOrganizationsPresenterImpl implements FindMyOwnOrganizationsPresenter
{
    /**
     * @param FindMyOwnOrganizationsResponse $response
     *
     * @return FindMyOwnOrganizationsResponse
     */
    public function present(FindMyOwnOrganizationsResponse $response): FindMyOwnOrganizationsResponse
    {
        return $response;
    }
}
