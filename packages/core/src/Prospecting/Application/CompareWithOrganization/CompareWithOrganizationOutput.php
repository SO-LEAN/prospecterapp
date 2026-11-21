<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\CompareWithOrganization;

use Prospecter\Core\SharedKernel\Application\Comparison;
use Prospecter\Core\SharedKernel\Application\OutputPort;

interface CompareWithOrganizationOutput extends OutputPort
{
    public function present(Comparison $comparison): void;
}
