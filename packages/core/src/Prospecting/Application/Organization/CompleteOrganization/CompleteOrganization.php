<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Organization\CompleteOrganization;

/**
 * Fills an organization's empty fields with what a client knows about it. It never changes a recorded value
 * (ADR 0177).
 */
final readonly class CompleteOrganization
{
    /**
     * @param list<string> $links its links' addresses
     */
    public function __construct(
        public string $prospectorId,
        public string $id,
        public ?string $siret,
        public array $links,
        public ?string $website,
        public ?string $email,
        public ?string $phoneNumber,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
