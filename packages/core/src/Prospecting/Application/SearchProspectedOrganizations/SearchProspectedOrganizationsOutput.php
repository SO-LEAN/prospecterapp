<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations;

use Prospecter\Core\SharedKernel\Application\OutputPort;

interface SearchProspectedOrganizationsOutput extends OutputPort
{
    public function present(SearchProspectedOrganizationsResponse $page): void;
}
