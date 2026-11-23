<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts;

interface ListOrganizationFactsOutput
{
    /**
     * @param list<OrganizationFactsResponse> $facts none for an unknown account
     * @param string|null                     $next  the next slice's cursor, null when this slice is the last
     */
    public function present(array $facts, ?string $next): void;
}
