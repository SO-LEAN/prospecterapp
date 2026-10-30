<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ConsultPerson;

use Prospecter\Core\PersonalData\Application\PersonCard;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptPerson implements ConsultPersonOutput
{
    public private(set) PersonCard $person;

    public function present(PersonCard $person): void
    {
        $this->person = $person;
    }
}
