<?php

namespace App\Booking\Resource;

use OpenApi\Attributes as OA;

/**
 * When the host may be booked on a day of the week, on their clock.
 */
final readonly class WindowResource
{
    public function __construct(
        #[OA\Property(enum: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])]
        public string $weekday,
        #[OA\Property(description: 'HH:MM, on the quarter hour', example: '14:00')]
        public string $from,
        #[OA\Property(description: 'HH:MM, on the quarter hour, 15 minutes after the start at least', example: '17:00')]
        public string $to,
    ) {
    }
}
