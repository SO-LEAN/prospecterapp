<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ChangeContactDetails;

final readonly class ChangeContactDetails
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public ?string $email,
        public ?string $phoneNumber,
        public ?string $language,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
