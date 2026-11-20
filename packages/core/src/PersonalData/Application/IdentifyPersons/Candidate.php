<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\IdentifyPersons;

/**
 * A contact of the account the clues may designate, named as every member of the account reads them.
 */
final readonly class Candidate
{
    public function __construct(
        public string $id,
        public string $name,
    ) {
    }
}
