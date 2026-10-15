<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase\FindMyOwnOrganizations;

interface FindMyOwnOrganizationsPresenter
{
    public function present(FindMyOwnOrganizationsResponse $response): mixed;
}
