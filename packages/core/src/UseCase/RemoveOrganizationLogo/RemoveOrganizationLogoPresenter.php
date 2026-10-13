<?php

declare( strict_types = 1 );

namespace Prospecter\Core\UseCase\RemoveOrganizationLogo;

interface RemoveOrganizationLogoPresenter
{
    public function present(RemoveOrganizationLogoResponse $response);
}
