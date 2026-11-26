<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\IdentifyOrganizations;

/**
 * An organization as the client sees it. It holds what the client knows about it. Each clue is optional.
 */
final readonly class OrganizationClues
{
    /**
     * @param list<string> $links
     * @param list<string> $emails the organization's addresses, or those of people who work there
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
