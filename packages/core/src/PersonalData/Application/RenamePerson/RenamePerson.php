<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\RenamePerson;

final readonly class RenamePerson
{
    public function __construct(
        public string $readerId,
        public string $id,
        public ?string $firstName,
        public ?string $lastName,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
