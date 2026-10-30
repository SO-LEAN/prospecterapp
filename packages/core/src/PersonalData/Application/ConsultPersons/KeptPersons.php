<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ConsultPersons;

use Prospecter\Core\PersonalData\Application\PersonCard;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptPersons implements ConsultPersonsOutput
{
    /**
     * @var array<string, PersonCard>
     */
    public private(set) array $persons;

    /**
     * @param array<string, PersonCard> $persons by person id
     */
    public function present(array $persons): void
    {
        $this->persons = $persons;
    }
}
