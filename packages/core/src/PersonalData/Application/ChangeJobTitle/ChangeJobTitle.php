<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ChangeJobTitle;

final readonly class ChangeJobTitle
{
    public function __construct(
        public string $readerId,
        public string $id,
        public ?string $jobTitle,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
