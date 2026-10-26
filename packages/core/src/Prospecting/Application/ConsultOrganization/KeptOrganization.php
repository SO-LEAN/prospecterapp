<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultOrganization;

use Prospecter\Core\Prospecting\Application\OrganizationResponse;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0084).
 */
final class KeptOrganization implements ConsultOrganizationOutput
{
    public private(set) OrganizationResponse $organization;

    public function present(OrganizationResponse $organization): void
    {
        $this->organization = $organization;
    }
}
