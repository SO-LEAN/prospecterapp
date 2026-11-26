<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Identification\IdentifyPersonsAndOrganizations;

use Prospecter\Core\PersonalData\Application\Identification\Identification;

/**
 * Keeps the answer as it is, for a client that reads it instead of showing it (ADR 0085).
 */
final class KeptPersonsAndOrganizations implements IdentifyPersonsAndOrganizationsOutput
{
    /** @var list<Identification> */
    public private(set) array $persons = [];

    /** @var list<Identification> */
    public private(set) array $organizations = [];

    public function present(array $persons, array $organizations): void
    {
        $this->persons = $persons;
        $this->organizations = $organizations;
    }
}
