<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptOrganizationPage implements SearchProspectedOrganizationsOutput
{
    public private(set) FoundOrganizationPage $page;

    public function present(FoundOrganizationPage $page): void
    {
        $this->page = $page;
    }
}
