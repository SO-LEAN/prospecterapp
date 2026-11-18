<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application;

/**
 * Whom a booker meets: the name and the picture of the host, and the organization they work for (ADR 0153).
 */
final readonly class PublicHost
{
    public function __construct(
        public ?string $firstName,
        public ?string $lastName,
        public ?string $pictureUrl,
        public ?string $organization,
    ) {
    }
}
