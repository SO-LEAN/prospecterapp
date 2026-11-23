<?php

declare(strict_types=1);

namespace App\Booking\Resource;

use OpenApi\Attributes as OA;

/**
 * A booking page as anyone with its link reads it, without signing in: who the booker meets, how, and the host's free
 * slots. It holds nothing when it offers no slot (ADR 0153).
 */
final readonly class PublicBookingPageResource
{
    /**
     * @param list<string>                                  $channels
     * @param list<array{day: string, slots: list<string>}> $days
     */
    public function __construct(
        #[OA\Property(description: 'Whether the page offers slots. A closed, changed, unknown or suspended page offers none and says nothing else')]
        public bool $open,
        #[OA\Property(description: 'Who the booker meets. Null when the page offers nothing')]
        public ?PublicHostResource $host,
        #[OA\Property(type: 'array', items: new OA\Items(type: 'string', enum: ['call', 'videoCall']))]
        public array $channels,
        #[OA\Property(description: 'The host\'s clock, on which the days and the slots are given. Null when the page offers nothing', example: 'Europe/Paris')]
        public ?string $timeZone,
        #[OA\Property(description: 'The 14 days from tomorrow on the host\'s clock, each with the start times of its free 15-minute slots. Empty when the page offers nothing', type: 'array', items: new OA\Items(properties: [
            new OA\Property(property: 'day', type: 'string', format: 'date'),
            new OA\Property(property: 'slots', type: 'array', items: new OA\Items(type: 'string', format: 'date-time')),
        ], type: 'object', required: ['day', 'slots']))]
        public array $days,
    ) {
    }
}
