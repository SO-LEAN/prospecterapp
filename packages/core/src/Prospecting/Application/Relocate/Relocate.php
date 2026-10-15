<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Relocate;

/**
 * Moves the organization to an address; an address without any part erases it.
 */
final readonly class Relocate
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public ?string $street,
        public ?string $postalCode,
        public ?string $city,
        public ?string $country,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
