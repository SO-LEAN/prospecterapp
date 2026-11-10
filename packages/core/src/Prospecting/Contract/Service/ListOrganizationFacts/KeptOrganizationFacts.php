<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptOrganizationFacts implements ListOrganizationFactsOutput
{
    /**
     * @var list<OrganizationFactsResponse>
     */
    public private(set) array $facts = [];
    public private(set) ?string $next = null;

    /**
     * @param list<OrganizationFactsResponse> $facts
     */
    public function present(array $facts, ?string $next): void
    {
        $this->facts = $facts;
        $this->next = $next;
    }
}
