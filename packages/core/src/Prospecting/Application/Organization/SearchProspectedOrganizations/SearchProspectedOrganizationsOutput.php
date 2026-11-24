<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Organization\SearchProspectedOrganizations;

use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface SearchProspectedOrganizationsOutput extends OutputPort
{
    public function present(FoundOrganizationPage $page): void;
}
