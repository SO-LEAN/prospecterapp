<?php

namespace App\Booking\Resource;

use OpenApi\Attributes as OA;

/**
 * A booking page as anyone holding its link reads it, without signing in: nothing of its host but that.
 */
final readonly class PublicBookingPageResource
{
    /**
     * @param list<string>                                  $channels
     * @param list<array{day: string, slots: list<string>}> $days
     */
    public function __construct(
        #[OA\Property(description: 'Whether it offers slots: closed, changed, unknown or suspended, it offers none')]
        public bool $open,
        #[OA\Property(type: 'array', items: new OA\Items(type: 'string', enum: ['call', 'videoCall']))]
        public array $channels,
        #[OA\Property(description: 'The 14 days from tomorrow on the clock of the host, each with the moments its free slots of 15 minutes start at; none when it shows nothing', type: 'array', items: new OA\Items(properties: [
            new OA\Property(property: 'day', type: 'string', format: 'date'),
            new OA\Property(property: 'slots', type: 'array', items: new OA\Items(type: 'string', format: 'date-time')),
        ], type: 'object'))]
        public array $days,
    ) {
    }
}
