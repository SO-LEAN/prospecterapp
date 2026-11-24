<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Organization\ConsultOrganization;

use Prospecter\Core\Prospecting\Application\OrganizationSheet;

/**
 * Keeps the answer unchanged, for a client that reads it instead of showing it (ADR 0085).
 */
final class KeptOrganization implements ConsultOrganizationOutput
{
    public private(set) OrganizationSheet $organization;

    public function present(OrganizationSheet $organization): void
    {
        $this->organization = $organization;
    }
}
