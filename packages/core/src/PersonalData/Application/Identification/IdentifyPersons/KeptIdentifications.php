<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Identification\IdentifyPersons;

use Prospecter\Core\PersonalData\Application\Identification\Identification;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptIdentifications implements IdentifyPersonsOutput
{
    /** @var list<Identification> */
    public private(set) array $identifications = [];

    public function present(array $identifications): void
    {
        $this->identifications = $identifications;
    }
}
