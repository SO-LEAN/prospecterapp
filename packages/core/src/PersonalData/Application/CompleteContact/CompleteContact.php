<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\CompleteContact;

/**
 * Fills what a contact lacks with what a client knows of them, never changing what is noted (ADR 0177).
 */
final readonly class CompleteContact
{
    /**
     * @param list<string> $links the addresses of their links
     */
    public function __construct(
        public string $readerId,
        public string $id,
        public string $civility,
        public ?string $jobTitle,
        public ?string $email,
        public ?string $phoneNumber,
        public array $links,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
