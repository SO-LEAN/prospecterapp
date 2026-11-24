<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Contact\CompleteContact;

/**
 * Fills a contact's empty fields with what a client knows about them. It never changes what is already logged
 * (ADR 0177).
 */
final readonly class CompleteContact
{
    /**
     * @param list<string> $links their links' addresses
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
