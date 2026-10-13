<?php

declare( strict_types = 1 );

namespace Prospecter\Core\UseCase\CreateOrganization;

interface CreateOrganizationPresenter
{
    public function present(CreateOrganizationResponse $response);
}
