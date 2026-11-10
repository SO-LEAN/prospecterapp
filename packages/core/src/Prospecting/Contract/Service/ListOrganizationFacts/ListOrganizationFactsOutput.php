<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts;

interface ListOrganizationFactsOutput
{
    /**
     * @param list<OrganizationFactsResponse> $facts none for an unknown account
     * @param string|null                     $next  the cursor of the next slice, null when this one is the last
     */
    public function present(array $facts, ?string $next): void;
}
