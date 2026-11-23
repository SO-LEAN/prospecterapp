<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application;

/**
 * A contact in the same organization whom another contact resembles by name. They may be the same person.
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
