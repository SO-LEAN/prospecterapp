<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultOrganization;

use Prospecter\Core\Prospecting\Application\OrganizationResponse;
use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ConsultOrganizationOutput extends OutputPort
{
    public function present(OrganizationResponse $organization): void;
}
