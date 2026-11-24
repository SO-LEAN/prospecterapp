<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Organization\CompareWithOrganization;

use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;
use Prospecter\Core\SharedKernel\Application\Response\Comparison;

interface CompareWithOrganizationOutput extends OutputPort
{
    public function present(Comparison $comparison): void;
}
