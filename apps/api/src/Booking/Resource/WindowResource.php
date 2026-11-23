<?php

declare(strict_types=1);

namespace App\Booking\Resource;

use OpenApi\Attributes as OA;

/**
 * When the host is bookable on a weekday, on their clock.
 */
final readonly class WindowResource
{
    public function __construct(
        #[OA\Property(enum: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])]
        public string $weekday,
        #[OA\Property(description: 'HH:MM, on the quarter hour', example: '14:00')]
        public string $from,
        #[OA\Property(description: 'HH:MM, on the quarter hour, at least 15 minutes after the start', example: '17:00')]
        public string $to,
    ) {
    }
}
