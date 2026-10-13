<?php

declare( strict_types = 1 );

namespace Prospecter\Core\UseCase\GetOrganization;

interface GetOrganizationPresenter
{
    public function present(GetOrganizationResponse $response);
}
