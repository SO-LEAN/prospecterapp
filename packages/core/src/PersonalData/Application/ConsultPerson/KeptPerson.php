<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ConsultPerson;

use Prospecter\Core\PersonalData\Application\PersonResponse;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptPerson implements ConsultPersonOutput
{
    public private(set) PersonResponse $person;

    public function present(PersonResponse $person): void
    {
        $this->person = $person;
    }
}
