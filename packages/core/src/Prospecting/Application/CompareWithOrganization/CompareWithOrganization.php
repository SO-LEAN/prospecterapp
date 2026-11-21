<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\CompareWithOrganization;

/**
 * What completing an organization with what a client knows of it would fill, and what it gives otherwise than noted
 * (ADR 0177), before completing it.
 */
final readonly class CompareWithOrganization
{
    /**
     * @param list<string> $links the addresses of its links
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
