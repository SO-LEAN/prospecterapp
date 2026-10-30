<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\RecognizeCallingOrganization;

use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\SharedKernel\Application\OutputPort;

interface RecognizeCallingOrganizationOutput extends OutputPort
{
    /**
     * @param list<OrganizationSheet> $organizations the organizations having the number
     */
    public function present(array $organizations): void;
}
