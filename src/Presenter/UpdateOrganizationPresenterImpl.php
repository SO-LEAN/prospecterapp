<?php

namespace App\Presenter;

use Solean\CleanProspecter\UseCase\UpdateOrganization\UpdateOrganizationPresenter;
use Solean\CleanProspecter\UseCase\UpdateOrganization\UpdateOrganizationResponse;

class UpdateOrganizationPresenterImpl implements UpdateOrganizationPresenter
{
    /**
     * @param UpdateOrganizationResponse $response
     *
     * @return UpdateOrganizationResponse
     */
    public function present(UpdateOrganizationResponse $response): UpdateOrganizationResponse
    {
        return $response;
    }
}
