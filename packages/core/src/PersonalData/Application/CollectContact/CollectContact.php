<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\CollectContact;

/**
 * Keeps a person the account prospects, under the identity the client gives it (ADR 0036, 0041).
 */
final readonly class CollectContact
{
    public function __construct(
        public string $readerId,
        public string $id,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $jobTitle,
        public ?string $email,
        public ?string $phoneNumber,
        public ?string $language,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
