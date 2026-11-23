<?php

declare(strict_types=1);

namespace App\Booking\Resource;

use OpenApi\Attributes as OA;

/**
 * What the booker booked, as they gave it, so that the page can confirm it.
 */
final readonly class BookingResource
{
    public function __construct(
        #[OA\Property(example: '2026-11-24T09:00:00+01:00')]
        public string $slot,
        #[OA\Property(enum: ['call', 'videoCall'])]
        public string $channel,
        #[OA\Property(description: 'Where the invitation goes', example: 'ada@acme.fr')]
        public string $email,
    ) {
    }
}
