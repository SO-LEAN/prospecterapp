<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\RelocateMyOrganization;

/**
 * Moves my organization to an address; an address without any part erases it.
 */
final readonly class RelocateMyOrganization
{
    public function __construct(
        public string $memberId,
        public ?string $street,
        public ?string $postalCode,
        public ?string $city,
        public ?string $country,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
