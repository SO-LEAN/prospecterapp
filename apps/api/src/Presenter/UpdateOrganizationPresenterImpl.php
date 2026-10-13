<?php

namespace App\Presenter;

use Prospecter\Core\UseCase\UpdateOrganization\UpdateOrganizationPresenter;
use Prospecter\Core\UseCase\UpdateOrganization\UpdateOrganizationResponse;

class UpdateOrganizationPresenterImpl implements UpdateOrganizationPresenter
{
    public function present(UpdateOrganizationResponse $response): UpdateOrganizationResponse
    {
        return $response;
    }
}
