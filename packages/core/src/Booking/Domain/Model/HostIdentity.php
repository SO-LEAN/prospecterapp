<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

/**
 * Who a host is to the bookers: their name, their picture and the organization they work for, as they present
 * themselves to whom they give their link.
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
