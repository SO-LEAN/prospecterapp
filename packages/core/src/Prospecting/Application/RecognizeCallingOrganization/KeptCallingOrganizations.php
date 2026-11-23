<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\RecognizeCallingOrganization;

use Prospecter\Core\Prospecting\Application\OrganizationSheet;

/**
 * Keeps the answer as it is, for a client that reads it instead of showing it (ADR 0085).
 */
final class KeptCallingOrganizations implements RecognizeCallingOrganizationOutput
{
    /**
     * @var list<OrganizationSheet>
     */
    public private(set) array $organizations;

    /**
     * @param list<OrganizationSheet> $organizations the organizations that have the number
     */
    public function present(array $organizations): void
    {
        $this->organizations = $organizations;
    }
}
