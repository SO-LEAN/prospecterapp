<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase\UpdateOrganization;

interface UpdateOrganizationPresenter
{
    public function present(UpdateOrganizationResponse $response): mixed;
}
