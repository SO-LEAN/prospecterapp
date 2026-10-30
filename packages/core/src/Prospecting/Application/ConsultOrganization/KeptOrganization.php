<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultOrganization;

use Prospecter\Core\Prospecting\Application\OrganizationSheet;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptOrganization implements ConsultOrganizationOutput
{
    public private(set) OrganizationSheet $organization;

    public function present(OrganizationSheet $organization): void
    {
        $this->organization = $organization;
    }
}
