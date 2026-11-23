<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

use Prospecter\Core\Campaigns\Domain\Policy\GeographicTags;

/**
 * Where an organization is, as Prospecting publishes it (ADR 0142). It holds the INSEE code of the municipality that
 * the geocoding returned, and the address's country. It sets the organization's geographic tags.
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
     * @param array{municipalityCode: ?string, country: ?string}|null $locality as read, null without a fact
     */
    public static function read(?array $locality): self
    {
        return null === $locality ? self::unknown() : new self($locality['municipalityCode'], $locality['country']);
    }

    /**
     * @return list<Tag> the surrounding big city, the department and the region, in that order
     */
    #[\NoDiscard]
    public function geographicTags(): array
    {
        return GeographicTags::of($this);
    }
}
