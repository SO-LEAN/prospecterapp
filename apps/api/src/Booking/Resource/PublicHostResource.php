<?php

declare(strict_types=1);

namespace App\Booking\Resource;

use App\Shared\Resource\ImageResource;

/**
 * Who a booker meets: the host's name and picture, and the organization they work for.
 */
final readonly class PublicHostResource
{
    public function __construct(
        public ?string $firstName,
        public ?string $lastName,
        public ?ImageResource $picture,
        public ?string $organization,
    ) {
    }
}
