<?php

namespace App\Presenter;

use Solean\CleanProspecter\UseCase\UpdateOrganization\UpdateOrganizationPresenter;
use Solean\CleanProspecter\UseCase\UpdateOrganization\UpdateOrganizationResponse;

class UpdateOrganizationPresenterImpl implements UpdateOrganizationPresenter
{
    public function present(UpdateOrganizationResponse $response): UpdateOrganizationResponse
    {
        return $response;
    }
}
