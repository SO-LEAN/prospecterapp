<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\IdentifyOrganizations;

/**
 * Keeps the answer as it is, for a client that reads it instead of showing it (ADR 0085).
 */
final class KeptOrganizationIdentifications implements IdentifyOrganizationsOutput
{
    /** @var list<IdentificationResponse> */
    public private(set) array $identifications = [];

    public function present(array $identifications): void
    {
        $this->identifications = $identifications;
    }
}
