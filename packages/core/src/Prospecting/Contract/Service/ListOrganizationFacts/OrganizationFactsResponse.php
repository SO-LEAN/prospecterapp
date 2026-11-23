<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts;

/**
 * An organization's facts, the part another context counts. They say whether the account prospects it and whether
 * it is placed on the map. They give the persons it names as its contacts. They give the INSEE code of the
 * municipality its geocoding found and its address's country (ADR 0142). They give its state's revision, which each
 * change increments.
 */
final readonly class OrganizationFactsResponse
{
    private const int NO_REVISION = 0;

    /**
     * @param list<string> $contacts
     */
    public function __construct(
        public string $organizationId,
        public int $revision,
        public bool $present,
        public bool $located,
        public array $contacts,
        public ?string $municipalityCode,
        public ?string $country,
    ) {
    }

    /**
     * The last facts of an organization the account no longer prospects.
     */
    public static function absent(string $organizationId): self
    {
        return new self($organizationId, self::NO_REVISION, false, false, [], null, null);
    }
}
