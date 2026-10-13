<?php

namespace App\Presenter;

use Prospecter\Core\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsPresenter;
use Prospecter\Core\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsResponse;

class FindMyOwnOrganizationsPresenterImpl implements FindMyOwnOrganizationsPresenter
{
    public function present(FindMyOwnOrganizationsResponse $response): FindMyOwnOrganizationsResponse
    {
        return $response;
    }
}
