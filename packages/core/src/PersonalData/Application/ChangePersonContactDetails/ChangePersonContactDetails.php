<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ChangePersonContactDetails;

final readonly class ChangePersonContactDetails
{
    public function __construct(
        public string $readerId,
        public string $id,
        public ?string $email,
        public ?string $phoneNumber,
        public ?string $language,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
