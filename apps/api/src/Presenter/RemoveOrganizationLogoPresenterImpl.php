<?php

namespace App\Presenter;

use Prospecter\Core\UseCase\RemoveOrganizationLogo\RemoveOrganizationLogoPresenter;
use Prospecter\Core\UseCase\RemoveOrganizationLogo\RemoveOrganizationLogoResponse;

class RemoveOrganizationLogoPresenterImpl implements RemoveOrganizationLogoPresenter
{
    public function present(RemoveOrganizationLogoResponse $response): RemoveOrganizationLogoResponse
    {
        return $response;
    }
}
