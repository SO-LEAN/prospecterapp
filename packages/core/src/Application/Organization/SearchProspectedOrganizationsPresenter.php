<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization;

interface SearchProspectedOrganizationsPresenter
{
    public function present(SearchProspectedOrganizationsResponse $response): mixed;
}
