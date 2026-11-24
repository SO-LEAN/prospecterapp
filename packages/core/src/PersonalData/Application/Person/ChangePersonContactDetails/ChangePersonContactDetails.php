<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Person\ChangePersonContactDetails;

final readonly class ChangePersonContactDetails
{
    /**
     * @param list<string> $links their links' addresses
     */
    public function __construct(
        public string $readerId,
        public string $id,
        public ?string $email,
        public ?string $phoneNumber,
        public ?string $language,
        public array $links,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
