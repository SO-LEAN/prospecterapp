<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\IdentifyOrganizations;

/**
 * An organization as the client sees it: what it knows of it, each clue optional.
 */
final readonly class OrganizationClues
{
    /**
     * @param list<string> $links
     * @param list<string> $emails the addresses of the organization, or of persons working there
     */
    public function __construct(
        public ?string $name = null,
        public ?string $siret = null,
        public array $links = [],
        public ?string $website = null,
        public array $emails = [],
        public ?string $city = null,
    ) {
    }
}
