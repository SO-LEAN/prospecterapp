<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application;

/**
 * Who a booker meets: the host's name and picture, and the organization they work for (ADR 0153).
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
