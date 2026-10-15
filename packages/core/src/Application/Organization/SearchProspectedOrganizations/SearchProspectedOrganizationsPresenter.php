<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization\SearchProspectedOrganizations;

interface SearchProspectedOrganizationsPresenter
{
    public function present(SearchProspectedOrganizationsResponse $response): mixed;
}
