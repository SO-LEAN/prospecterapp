<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\DescribePersons;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptPersonDescriptions implements DescribePersonsOutput
{
    /**
     * @var list<PersonDescriptionResponse>
     */
    public private(set) array $persons = [];

    public function present(array $persons): void
    {
        $this->persons = $persons;
    }
}
