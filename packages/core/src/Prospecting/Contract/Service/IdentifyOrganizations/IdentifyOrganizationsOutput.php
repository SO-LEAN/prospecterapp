<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\IdentifyOrganizations;

interface IdentifyOrganizationsOutput
{
    /**
     * @param list<IdentificationResponse> $identifications one per organization in the query, in the query's order
     */
    public function present(array $identifications): void;
}
