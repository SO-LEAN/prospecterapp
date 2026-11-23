<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultOrganization;

use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface ConsultOrganizationOutput extends OutputPort
{
    public function present(OrganizationSheet $organization): void;
}
