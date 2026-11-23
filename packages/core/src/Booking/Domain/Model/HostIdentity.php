<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

/**
 * How a host presents themselves to the people they give their link to: their name, their picture and the organization
 * they work for.
 */
final readonly class HostIdentity
{
    public function __construct(
        public ?string $firstName,
        public ?string $lastName,
        public ?string $pictureUrl,
        public ?string $organization,
    ) {
    }
}
