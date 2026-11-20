<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application;

/**
 * A contact of the same organization another one resembles, by their names: perhaps the same person.
 */
final readonly class PossibleDuplicate
{
    public function __construct(
        public string $id,
        public ?string $firstName,
        public ?string $lastName,
    ) {
    }
}
