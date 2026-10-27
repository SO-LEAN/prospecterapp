<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\RecognizeCallingOrganization;

use Prospecter\Core\Prospecting\Application\OrganizationResponse;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptCallingOrganizations implements RecognizeCallingOrganizationOutput
{
    /**
     * @var list<OrganizationResponse>
     */
    public private(set) array $organizations;

    /**
     * @param list<OrganizationResponse> $organizations the organizations having the number
     */
    public function present(array $organizations): void
    {
        $this->organizations = $organizations;
    }
}
