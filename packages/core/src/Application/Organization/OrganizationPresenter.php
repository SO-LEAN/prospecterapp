<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization;

interface OrganizationPresenter
{
    public function present(OrganizationResponse $response): mixed;
}
