<?php

declare(strict_types=1);

namespace App\Booking\Resource;

use OpenApi\Attributes as OA;

/**
 * A booking page as anyone holding its link reads it, without signing in: whom the booker meets, how, and the free
 * slots of its host; nothing at all when it offers none (ADR 0153).
 */
final readonly class PublicBookingPageResource
{
    /**
     * @param list<string>                                  $channels
     * @param list<array{day: string, slots: list<string>}> $days
     */
    public function __construct(
        #[OA\Property(description: 'Whether it offers slots: closed, changed, unknown or suspended, it offers none and says nothing else')]
        public bool $open,
        #[OA\Property(description: 'Whom the booker meets; none when it offers nothing')]
        public ?PublicHostResource $host,
        #[OA\Property(type: 'array', items: new OA\Items(type: 'string', enum: ['call', 'videoCall']))]
        public array $channels,
        #[OA\Property(description: 'The clock of the host, which the days and the slots are on; none when it offers nothing', example: 'Europe/Paris')]
        public ?string $timeZone,
        #[OA\Property(description: 'The 14 days from tomorrow on the clock of the host, each with the moments its free slots of 15 minutes start at; none when it offers nothing', type: 'array', items: new OA\Items(properties: [
            new OA\Property(property: 'day', type: 'string', format: 'date'),
            new OA\Property(property: 'slots', type: 'array', items: new OA\Items(type: 'string', format: 'date-time')),
        ], type: 'object', required: ['day', 'slots']))]
        public array $days,
    ) {
    }
}
