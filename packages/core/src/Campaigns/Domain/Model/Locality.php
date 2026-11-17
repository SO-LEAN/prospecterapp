<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

use Prospecter\Core\Campaigns\Domain\Policy\GeographicTags;

/**
 * Where an organization is, as Prospecting publishes it (ADR 0142): the INSEE code of the municipality its geocoding
 * gave, and the country of its address; the geographic tags it sets.
 */
final readonly class Locality
{
    public function __construct(
        public ?string $municipalityCode,
        public ?string $country,
    ) {
    }

    public static function unknown(): self
    {
        return new self(null, null);
    }

    /**
     * @param array{municipalityCode: ?string, country: ?string}|null $locality as it is read, null without a fact
     */
    public static function read(?array $locality): self
    {
        return null === $locality ? self::unknown() : new self($locality['municipalityCode'], $locality['country']);
    }

    /**
     * @return list<Tag> the big city around, the department and the region, the city first
     */
    #[\NoDiscard]
    public function geographicTags(): array
    {
        return GeographicTags::of($this);
    }
}
