<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\IdentifyOrganizations;

/**
 * Keeps the answer as it is, for a client that reads it instead of showing it (ADR 0085).
 */
final class KeptIdentifications implements IdentifyOrganizationsOutput
{
    /** @var list<Identification> */
    public private(set) array $identifications = [];

    public function present(array $identifications): void
    {
        $this->identifications = $identifications;
    }
}
