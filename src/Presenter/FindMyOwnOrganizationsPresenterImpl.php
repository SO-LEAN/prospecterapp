<?php

namespace App\Presenter;

use Solean\CleanProspecter\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsPresenter;
use Solean\CleanProspecter\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsResponse;

class FindMyOwnOrganizationsPresenterImpl implements FindMyOwnOrganizationsPresenter
{
    public function present(FindMyOwnOrganizationsResponse $response): FindMyOwnOrganizationsResponse
    {
        return $response;
    }
}
