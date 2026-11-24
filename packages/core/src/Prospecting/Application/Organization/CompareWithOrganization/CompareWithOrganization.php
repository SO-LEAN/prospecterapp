<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Organization\CompareWithOrganization;

/**
 * Tells, before a completion, what completing an organization with a client's knowledge would fill. It also tells
 * which values the client gives differently from those recorded (ADR 0177).
 */
final readonly class CompareWithOrganization
{
    /**
     * @param list<string> $links its links' addresses
     */
    public function __construct(
        public string $id,
        public ?string $siret,
        public array $links,
        public ?string $website,
        public ?string $email,
        public ?string $phoneNumber,
    ) {
    }
}
