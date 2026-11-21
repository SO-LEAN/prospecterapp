<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\CompleteOrganization;

/**
 * Fills what an organization lacks with what a client knows of it, never changing what is noted (ADR 0177).
 */
final readonly class CompleteOrganization
{
    /**
     * @param list<string> $links the addresses of its links
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
